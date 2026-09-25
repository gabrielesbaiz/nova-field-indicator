<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Console;

use Gabrielesbaiz\NovaFieldIndicator\Support\ColorResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SplFileInfo;
use Throwable;

/**
 * Migrates resources from the 2.x API to 3.0.
 *
 * 3.0 is a clean break with no deprecation shims, so this command is the
 * migration path. It rewrites what can be rewritten mechanically and reports
 * everything that needs a human, rather than guessing.
 *
 * Rewriting is offset-based rather than line-based on purpose: fluent chains
 * wrap across lines, so ->shouldHide(\n    fn ($v) => ...\n) has to be located
 * as one balanced unit. A line regex would corrupt multi-line closures.
 */
class UpgradeCommand extends Command
{
    protected $signature = 'nova-field-indicator:upgrade
                            {--path=app/Nova : Directory to scan for NovaFieldIndicator usage}
                            {--dry-run : Report what would change without writing}';

    protected $description = 'Migrate NovaFieldIndicator usage from 2.x to the 3.0 API';

    /**
     * Tailwind v1 hexes from the 2.x stylesheet, mapped to 3.0 tokens.
     *
     * @var array<string, string>
     */
    protected array $legacyHexes = [
        '#e3342f' => 'red',
        '#f6993f' => 'orange',
        '#ffed4a' => 'yellow',
        '#38c172' => 'green',
        '#4dc0b5' => 'teal',
        '#3490dc' => 'blue',
        '#6574cd' => 'indigo',
        '#9561e2' => 'purple',
        '#f66d9b' => 'pink',
        '#b8c2cc' => 'grey',
        '#22292f' => 'black',
    ];

    public function handle(): int
    {
        $path = base_path((string) $this->option('path'));

        if (! File::isDirectory($path)) {
            $this->components->error("Directory [{$path}] does not exist.");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $files = $this->findFiles($path);

        if ($files === []) {
            $this->components->warn('No NovaFieldIndicator usage found.');

            return self::SUCCESS;
        }

        $this->components->info(sprintf('Found %d file(s) using NovaFieldIndicator.', count($files)));

        $changedFiles = 0;
        $rewrites = 0;
        $reports = [
            'callable' => [],
            'strict' => [],
            'ambiguous' => [],
            'contradictory' => [],
            'resolveForDisplay' => [],
            'color' => [],
            'frontend' => [],
            'enum' => [],
        ];

        foreach ($files as $file) {
            $original = File::get($file);
            [$updated, $count] = $this->rewrite($original);

            if ($count > 0) {
                $rewrites += $count;
                $changedFiles++;

                if (! $dryRun) {
                    File::put($file, $updated);
                }
            }

            $this->collectReports($file, $original, $reports);
        }

        $this->newLine();
        $this->components->twoColumnDetail('Files rewritten', (string) $changedFiles);
        $this->components->twoColumnDetail('Calls rewritten', (string) $rewrites);

        $needsReview = $this->report($reports);

        $this->newLine();
        $this->components->twoColumnDetail('Needs review', (string) $needsReview);

        if ($dryRun) {
            $this->newLine();
            $this->components->warn('Dry run: no files were written.');
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    protected function findFiles(string $path): array
    {
        return collect(File::allFiles($path))
            ->filter(static fn (SplFileInfo $file): bool => $file->getExtension() === 'php')
            ->map(static fn (SplFileInfo $file): string => $file->getRealPath())
            ->filter(static fn (string $file): bool => str_contains(File::get($file), 'NovaFieldIndicator'))
            ->values()
            ->all();
    }

    /**
     * @return array{0: string, 1: int}
     */
    protected function rewrite(string $contents): array
    {
        $count = 0;

        // Pure renames. shouldHide() keeps its argument untouched; only the
        // cases flagged below actually change behaviour.
        foreach (['shouldHideIfNo' => 'hideWhenEmpty', 'shouldHide' => 'hideWhen'] as $from => $to) {
            $replaced = str_replace('->'.$from.'(', '->'.$to.'(', $contents, $n);
            $contents = $replaced;
            $count += $n;
        }

        // withoutLabels() was a no-op unless combined with labels(); where it
        // was a no-op it can simply go. The contradictory case is reported.
        if (! str_contains($contents, '->labels(')) {
            $contents = str_replace(['->withoutLabels()', '->withoutLabels ()'], '', $contents, $n);
            $count += $n;
        }

        // These four rendered an invisible dot on Nova 5, because the CSS
        // variables they referenced do not exist there.
        foreach (['success', 'danger', 'warning', 'info'] as $token) {
            foreach (["'var(--{$token})'", "\"var(--{$token})\""] as $literal) {
                $contents = str_replace($literal, "'{$token}'", $contents, $n);
                $count += $n;
            }
        }

        // Frozen Tailwind v1 hexes become tokens, so they follow the app theme.
        foreach ($this->legacyHexes as $hex => $token) {
            foreach ([strtolower($hex), strtoupper($hex)] as $variant) {
                foreach (["'{$variant}'", "\"{$variant}\""] as $literal) {
                    $contents = str_replace($literal, "'{$token}'", $contents, $n);
                    $count += $n;
                }
            }
        }

        // Now implied by Unfillable + exceptOnForms().
        $contents = (string) preg_replace(
            '/^[ \t]*public \$showOn(?:Creation|Update) = false;\R/m',
            '',
            $contents,
            -1,
            $n,
        );
        $count += $n;

        return [$contents, $count];
    }

    /**
     * @param  array<string, list<string>>  $reports
     */
    protected function collectReports(string $file, string $contents, array &$reports): void
    {
        $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);

        foreach ($this->locateCalls($contents, 'shouldHide') as $argument) {
            $trimmed = trim($argument);

            if ($trimmed === '') {
                continue;
            }

            // The silent biter: 2.x tested is_callable() first, so a string
            // naming a function was invoked rather than compared.
            if (preg_match('/^([\'"])([A-Za-z_][A-Za-z0-9_]*)\1$/', $trimmed, $m) === 1 && function_exists($m[2])) {
                $reports['callable'][] = sprintf('%s — ->shouldHide(%s)', $relative, $trimmed);

                continue;
            }

            if (preg_match('/^(0|1|true|false|\'0\'|"0")$/i', $trimmed) === 1) {
                $reports['strict'][] = sprintf('%s — ->shouldHide(%s)', $relative, $trimmed);

                continue;
            }

            if (str_starts_with($trimmed, '[') && preg_match('/^\[\s*\$this\s*,/', $trimmed) === 1) {
                $reports['ambiguous'][] = sprintf('%s — ->shouldHide(%s)', $relative, $trimmed);
            }
        }

        if (str_contains($contents, '->withoutLabels()') && str_contains($contents, '->labels(')) {
            $reports['contradictory'][] = $relative;
        }

        if (preg_match('/function\s+resolveForDisplay\s*\(/', $contents) === 1) {
            $reports['resolveForDisplay'][] = $relative;
        }

        foreach ($this->locateCalls($contents, 'colors') as $argument) {
            foreach ($this->unresolvableColors($argument) as $bad) {
                $reports['color'][] = sprintf('%s — %s', $relative, $bad);
            }
        }

        foreach (['.indicator-', 'indicator-field', "Nova::script('indicator'", 'field.labels', 'field.colors'] as $needle) {
            if (str_contains($contents, $needle)) {
                $reports['frontend'][] = sprintf('%s — references %s', $relative, $needle);
            }
        }

        if (str_contains($contents, '->labels(') && str_contains($contents, '->colors(')) {
            $reports['enum'][] = $relative;
        }
    }

    /**
     * Colour literals the real resolver cannot parse.
     *
     * Running the actual ColorResolver here means a typo surfaces during the
     * upgrade rather than as an exception on a production index.
     *
     * @return list<string>
     */
    protected function unresolvableColors(string $argument): array
    {
        $bad = [];
        $resolver = new ColorResolver;

        preg_match_all('/=>\s*([\'"])([^\'"]+)\1/', $argument, $matches);

        foreach ($matches[2] ?? [] as $literal) {
            try {
                $resolver->resolve($literal);
            } catch (Throwable) {
                $bad[] = sprintf('unresolvable colour "%s"', $literal);
            }
        }

        return $bad;
    }

    /**
     * Find each call to a fluent method and return its raw argument text.
     *
     * Balanced-paren and quote aware, so a multi-line closure argument comes
     * back in one piece.
     *
     * @return list<string>
     */
    protected function locateCalls(string $contents, string $method): array
    {
        $arguments = [];
        $needle = '->'.$method.'(';
        $offset = 0;

        while (($position = strpos($contents, $needle, $offset)) !== false) {
            $start = $position + strlen($needle);
            $depth = 1;
            $index = $start;
            $quote = null;
            $length = strlen($contents);

            while ($index < $length && $depth > 0) {
                $character = $contents[$index];

                if ($quote !== null) {
                    if ($character === '\\') {
                        $index += 2;

                        continue;
                    }

                    if ($character === $quote) {
                        $quote = null;
                    }
                } elseif ($character === '\'' || $character === '"') {
                    $quote = $character;
                } elseif ($character === '(') {
                    $depth++;
                } elseif ($character === ')') {
                    $depth--;
                }

                $index++;
            }

            $arguments[] = substr($contents, $start, $index - $start - 1);
            $offset = $index;
        }

        return $arguments;
    }

    /**
     * @param  array<string, list<string>>  $reports
     */
    protected function report(array $reports): int
    {
        $sections = [
            'callable' => 'Behaviour changed — 2.x INVOKED this string as a callback, 3.0 compares it as a value',
            'strict' => 'Comparison tightened — verify these still hide the rows you expect',
            'ambiguous' => 'Ambiguous argument — a callable array, or a list of values?',
            'contradictory' => 'withoutLabels() left in place because the file also uses labels() — remove it by hand',
            'resolveForDisplay' => 'Overrides resolveForDisplay() — resolution moved to jsonSerialize()/resolveIndicatorFor()',
            'color' => 'Colour literals the resolver rejects',
            'frontend' => 'References removed CSS classes, component names or payload keys',
            'enum' => 'Uses labels() + colors() together — candidate for ->enum()',
        ];

        $total = 0;

        foreach ($sections as $key => $title) {
            $entries = array_unique($reports[$key] ?? []);

            if ($entries === []) {
                continue;
            }

            $total += count($entries);

            $this->newLine();
            $this->components->warn($title);

            foreach ($entries as $entry) {
                $this->components->bulletList([$entry]);
            }
        }

        return $total;
    }
}
