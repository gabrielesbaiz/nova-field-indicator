<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Support;

use BackedEnum;
use Gabrielesbaiz\NovaFieldIndicator\Contracts\HasColor;
use Gabrielesbaiz\NovaFieldIndicator\Contracts\HasIcon;
use Gabrielesbaiz\NovaFieldIndicator\Contracts\HasLabel;
use Gabrielesbaiz\NovaFieldIndicator\Exceptions\IndicatorException;
use UnitEnum;

/**
 * Derives a state table from a backed enum.
 *
 * This is the feature that removes the most user code: three parallel arrays
 * that drift apart become one enum the application already owns. An enum that
 * implements none of the contracts still works — the case name is humanised for
 * the label — so pointing the field at an existing enum is never a no-op.
 *
 * Results are memoized per enum class. Cases cannot change within a request,
 * and without this the reflection would run once per row on an index.
 */
final class EnumStates
{
    /** @var array<class-string, array<array-key, State>> */
    private static array $cache = [];

    /**
     * @param  class-string  $enum
     * @return array<array-key, State>
     */
    public static function for(string $enum): array
    {
        if (isset(self::$cache[$enum])) {
            return self::$cache[$enum];
        }

        if (! enum_exists($enum)) {
            throw IndicatorException::notAnEnum($enum);
        }

        if (! is_a($enum, BackedEnum::class, true)) {
            throw IndicatorException::enumNotBacked($enum);
        }

        $states = [];

        /** @var list<BackedEnum&UnitEnum> $cases */
        $cases = $enum::cases();

        foreach ($cases as $case) {
            $states[$case->value] = new State(
                key: $case->value,
                label: $case instanceof HasLabel
                    ? ($case->indicatorLabel() ?? self::humanize($case->name))
                    : self::humanize($case->name),
                color: $case instanceof HasColor ? $case->indicatorColor() : null,
                icon: $case instanceof HasIcon ? $case->indicatorIcon() : null,
            );
        }

        return self::$cache[$enum] = $states;
    }

    /**
     * The option list a select filter needs, in declaration order.
     *
     * @param  class-string  $enum
     * @return list<string|int>
     */
    public static function values(string $enum): array
    {
        return array_keys(self::for($enum));
    }

    /**
     * Clear the memo. Only tests need this.
     */
    public static function flush(): void
    {
        self::$cache = [];
    }

    /**
     * Turn a case name into a readable label.
     *
     * Deliberately not Nova::humanize(): keeping Support/ free of Nova is what
     * lets the whole resolution layer be unit-tested without booting the app.
     */
    private static function humanize(string $name): string
    {
        $spaced = (string) preg_replace('/(?<!^)[A-Z]/', ' $0', $name);

        return ucfirst(strtolower(str_replace('_', ' ', $spaced)));
    }
}
