<?php

declare(strict_types=1);

namespace Gabrielesbaiz\NovaFieldIndicator\Results;

use Illuminate\Contracts\Support\Arrayable;

/**
 * A colour after resolution: finished CSS values, ready to become inline custom
 * properties.
 *
 * The client never inspects these strings. That is the point — 1.x classified
 * colours in the Vue component with an unanchored regex, which both duplicated
 * the palette on the wire and let a crafted literal escape into an inline
 * style. Classification now happens once, server-side, in ColorResolver.
 *
 * @implements Arrayable<string, string|null>
 */
final readonly class ResolvedColor implements Arrayable
{
    public function __construct(
        public string $light,
        public string $dark,
        public string $soft,
        public ?string $token = null,
    ) {}

    /**
     * @return array{token: string|null, light: string, dark: string, soft: string}
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'light' => $this->light,
            'dark' => $this->dark,
            'soft' => $this->soft,
        ];
    }
}
