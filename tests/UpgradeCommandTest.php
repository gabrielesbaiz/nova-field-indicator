<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->dir = base_path('app/Nova');
    File::ensureDirectoryExists($this->dir);
});

afterEach(function () {
    File::deleteDirectory(base_path('app'));
});

function writeResource(string $body): string
{
    $path = base_path('app/Nova/UserResource.php');
    File::put($path, $body);

    return $path;
}

function upgraded(string $body): string
{
    $path = writeResource($body);
    test()->artisan('nova-field-indicator:upgrade')->assertSuccessful();

    return File::get($path);
}

it('renames shouldHideIfNo and shouldHide', function () {
    $result = upgraded(<<<'PHP'
        <?php
        NovaFieldIndicator::make('Status')->shouldHideIfNo();
        NovaFieldIndicator::make('Other')->shouldHide('active');
        PHP);

    expect($result)->toContain('->hideWhenEmpty()')
        ->toContain("->hideWhen('active')")
        ->not->toContain('->shouldHide');
});

/*
 * The reason the scanner is balanced-paren rather than line-based: a naive
 * line regex would corrupt a closure that wraps across lines.
 */
it('rewrites a multi-line closure argument without corrupting it', function () {
    $result = upgraded(<<<'PHP'
        <?php
        NovaFieldIndicator::make('Status')->shouldHide(function ($value) {
            return $value === 'inactive';
        });
        PHP);

    expect($result)->toContain('->hideWhen(function ($value) {')
        ->toContain("return \$value === 'inactive';")
        ->toContain('});');
});

it('rewrites the four colour tokens that rendered invisibly on Nova 5', function () {
    $result = upgraded(<<<'PHP'
        <?php
        NovaFieldIndicator::make('Status')->colors([
            'a' => 'var(--success)',
            'b' => 'var(--danger)',
            'c' => 'var(--warning)',
            'd' => 'var(--info)',
        ]);
        PHP);

    expect($result)->toContain("'a' => 'success'")
        ->toContain("'b' => 'danger'")
        ->toContain("'c' => 'warning'")
        ->toContain("'d' => 'info'")
        ->not->toContain('var(--');
});

it('rewrites frozen Tailwind v1 hexes to tokens', function () {
    $result = upgraded(<<<'PHP'
        <?php
        NovaFieldIndicator::make('Status')->colors([
            'a' => '#E3342F',
            'b' => '#38C172',
            'c' => '#b8c2cc',
        ]);
        PHP);

    expect($result)->toContain("'a' => 'red'")
        ->toContain("'b' => 'green'")
        ->toContain("'c' => 'grey'");
});

it('drops withoutLabels when it was a no-op', function () {
    $result = upgraded(<<<'PHP'
        <?php
        NovaFieldIndicator::make('Status')->withoutLabels();
        PHP);

    expect($result)->not->toContain('withoutLabels');
});

it('keeps withoutLabels when the file also uses labels, and reports it', function () {
    $body = <<<'PHP'
        <?php
        NovaFieldIndicator::make('Status')->labels(['a' => 'A'])->withoutLabels();
        PHP;

    $path = writeResource($body);

    $this->artisan('nova-field-indicator:upgrade')
        ->expectsOutputToContain('left in place')
        ->assertSuccessful();

    expect(File::get($path))->toContain('withoutLabels');
});

it('removes the redundant form visibility properties', function () {
    $result = upgraded(<<<'PHP'
        <?php
        class Foo extends NovaFieldIndicator
        {
            public $showOnCreation = false;
            public $showOnUpdate = false;
            public $component = 'x';
        }
        PHP);

    expect($result)->not->toContain('showOnCreation')
        ->not->toContain('showOnUpdate')
        ->toContain("public \$component = 'x';");
});

/*
 * The silent biter: 2.x invoked this string as a callback because it tested
 * is_callable() first. It cannot be rewritten, only flagged.
 */
it('flags a shouldHide string that names a PHP function', function () {
    writeResource(<<<'PHP'
        <?php
        NovaFieldIndicator::make('Status')->shouldHide('trim');
        PHP);

    $this->artisan('nova-field-indicator:upgrade')
        ->expectsOutputToContain('INVOKED')
        ->assertSuccessful();
});

it('flags a shouldHide argument whose comparison tightened', function () {
    writeResource(<<<'PHP'
        <?php
        NovaFieldIndicator::make('Status')->shouldHide(0);
        PHP);

    $this->artisan('nova-field-indicator:upgrade')
        ->expectsOutputToContain('Comparison tightened')
        ->assertSuccessful();
});

it('flags a subclass that overrides resolveForDisplay', function () {
    writeResource(<<<'PHP'
        <?php
        class Foo extends NovaFieldIndicator
        {
            public function resolveForDisplay($resource, $attribute = null) {}
        }
        PHP);

    $this->artisan('nova-field-indicator:upgrade')
        ->expectsOutputToContain('resolveForDisplay')
        ->assertSuccessful();
});

it('flags a colour literal the resolver cannot parse', function () {
    writeResource(<<<'PHP'
        <?php
        NovaFieldIndicator::make('Status')->colors(['a' => 'blurple']);
        PHP);

    $this->artisan('nova-field-indicator:upgrade')
        ->expectsOutputToContain('unresolvable colour')
        ->assertSuccessful();
});

it('flags references to removed front-end names', function () {
    writeResource(<<<'PHP'
        <?php
        // custom css targets .indicator-brand and the indicator-field component
        NovaFieldIndicator::make('Status');
        PHP);

    $this->artisan('nova-field-indicator:upgrade')
        ->expectsOutputToContain('removed CSS classes')
        ->assertSuccessful();
});

it('suggests enum() when labels and colors are used together', function () {
    writeResource(<<<'PHP'
        <?php
        NovaFieldIndicator::make('Status')->labels(['a' => 'A'])->colors(['a' => 'success']);
        PHP);

    $this->artisan('nova-field-indicator:upgrade')
        ->expectsOutputToContain('candidate for ->enum()')
        ->assertSuccessful();
});

it('writes nothing on a dry run', function () {
    $body = <<<'PHP'
        <?php
        NovaFieldIndicator::make('Status')->shouldHideIfNo();
        PHP;

    $path = writeResource($body);

    $this->artisan('nova-field-indicator:upgrade', ['--dry-run' => true])->assertSuccessful();

    expect(File::get($path))->toBe($body);
});

it('is idempotent', function () {
    $body = <<<'PHP'
        <?php
        NovaFieldIndicator::make('Status')->shouldHide('active')->shouldHideIfNo();
        PHP;

    $path = writeResource($body);

    $this->artisan('nova-field-indicator:upgrade')->assertSuccessful();
    $once = File::get($path);

    $this->artisan('nova-field-indicator:upgrade')->assertSuccessful();

    expect(File::get($path))->toBe($once);
});

it('fails cleanly when the path does not exist', function () {
    $this->artisan('nova-field-indicator:upgrade', ['--path' => 'does/not/exist'])
        ->assertFailed();
});

it('reports when nothing uses the field', function () {
    File::put(base_path('app/Nova/Unrelated.php'), '<?php class Unrelated {}');

    $this->artisan('nova-field-indicator:upgrade')
        ->expectsOutputToContain('No NovaFieldIndicator usage found')
        ->assertSuccessful();
});
