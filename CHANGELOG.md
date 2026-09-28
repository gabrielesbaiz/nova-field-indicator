# Changelog

All notable changes to `nova-field-indicator` will be documented in this file.

## 2.0.1 - 2026-09-28

### Performance

Serializing a row is about **28% faster** — 0.086 ms to 0.062 ms per row for a
four-state `states()` table, 0.075 ms to 0.054 ms for `->enum()` (minimum of five
runs, PHP 8.4, 2000 rows per run). Nothing about the API or the payload changed.

- **One configuration read per field instead of eleven.** Shape, size, inline,
  empty label, default colour, token table, shades, soft alpha, icon switch, icon
  type and icon defaults each called `config()`, and Nova rebuilds the field for
  every row — a 50-row index with three indicator columns made over 1,600 container
  lookups. The package namespace is read once per field now.
- **The application token map is lowered once**, in the resolver's constructor.
  It was being rebuilt with `array_change_key_case()` inside both the membership
  test and the read, on every colour resolved.
- **Colour family and shade membership are hash lookups**, not linear scans over
  23 families and 11 shades.
- **`->ranges()` is matched once per value**, not twice. The band is resolved for
  the state and reused for the colour.
- Numeric state keys are detected with `ctype_digit()` rather than a regex, and
  the resolver memoizes colours per value within a field.

## 2.0.0 - 2026-09-25

A full rewrite. See [UPGRADE.md](UPGRADE.md) before upgrading — and run
`php artisan nova-field-indicator:upgrade` to migrate your call sites.

### Requirements

- Requires PHP 8.3+, Laravel 12 or 13, and Nova 5.11+ (Nova 6 is explicitly conflicted).
  The floor is 5.11 rather than 5.7 because `Field::configureDefaults()` — the hook the
  field applies `exceptOnForms()` and the `inline` default through — arrives in 5.11. The
  method carries `#[Override]`, so an older Nova fails loudly when the class is loaded
  rather than silently rendering the field on forms.
- Dropped PHP 8.0–8.2 and Laravel 10 and 11.
- `laravel/nova` is now a runtime requirement rather than a dev dependency.

### Fixed

- **`success`, `danger`, `warning` and `info` rendered an invisible dot on Nova 5.**
  They were styled as `var(--success)` and friends — variables Nova 3 and 4 defined
  and Nova 5 does not — so the mark painted with an invalid colour. If you used these
  tokens, dots will now appear where your index looked empty.
- **`shouldHide('trim')` silently invoked `trim()`** instead of comparing against the
  string, because `is_callable()` was tested before the value branch. `hideWhen()`
  dispatches on the declared type, so a string is always data.
- Colour literals are now validated server-side against an anchored allow-list before
  they reach an inline style. The 1.x client-side regex was unanchored and would have
  passed `#fff; background-image:url(…)` straight through.
- `whitespace-no-wrap` was Tailwind v1 syntax and had done nothing since Nova 4.
- The neutral colour no longer depends on stylesheet source order: 1.x always emitted
  an `indicator-grey` class alongside the real one and relied on declaration order to
  pick the winner.
- Dark mode is supported at all, for the first time.
- The generic `indicator` asset handle, which any other package could collide with.

### Added

- **`->enum(Status::class)`** derives labels, colours and icons from one enum. A value
  that *is* a backed enum self-describes with no configuration at all.
- `->states()` to configure a value's label, colour, icon, tooltip and pulse in one call.
- Shapes — `->dot()`, `->ring()`, `->pill()`, `->square()` — and three sizes.
- Icons, via Nova's own heroicon component, so they add nothing to the JS bundle.
- `->ranges()` for continuous values: scores, percentages, stock levels.
- `->boolean()`, `->labelUsing()`, `->colorUsing()`, `->iconUsing()`, `->tooltipUsing()`.
- `->pulse()`, gated behind `prefers-reduced-motion`.
- `->filterable()`: the field now implements `FilterableField` and builds its own
  select-filter options from the state table or the enum.
- Accessibility: the mark is decorative when a label is visible and becomes a named
  `role="img"` when colour alone carries the meaning. Forced-colors mode is handled.
- Arbitrary Tailwind palette colours (`emerald-600`), light/dark pairs, and literal
  CSS colours as first-class values.
- A publishable config file, including a `colors.tokens` table for rebranding a token
  once rather than at every call site.
- English and Italian translations.
- `php artisan nova-field-indicator:upgrade` to migrate from 1.x.
- Real test coverage: 164 Pest tests and 22 Vitest tests, PHPStan level 6.

### Changed

- **Labels and colours are resolved on the server.** 1.x pushed the whole configuration
  into every row of an index and looked it up again in the browser; the payload now
  carries one resolved indicator per value.
- Colours resolve to Nova's own `--colors-<family>-<shade>` variables, so re-theming
  Nova re-themes every indicator. The built-in names now match the Tailwind 3 palette.
- The field is `Unfillable` and hidden on every form view, including attach and
  update-attached, which the old `$showOnCreation`/`$showOnUpdate` pair missed.
- Vite 6 replaces Laravel Mix. Output paths are unchanged.
- The Vue component is now `nova-field-indicator`.
- The bundle is 1.07 kB gzipped, down from 1.4 kB, despite the added features.

### Removed

- `shouldHide()`, `shouldHideIfNo()` and `withoutLabels()` — see [UPGRADE.md](UPGRADE.md).
- The `.indicator-*` CSS classes.
- The `FormField` component, which was registered and bundled but unreachable.
- `spatie/laravel-package-tools`, which was required but never used.
- Fifteen npm dependencies, six of them (leaflet, vuex, inertia, scriptjs and friends)
  copy-pasted from `nova-field-map` and never referenced.

## 1.0.0

- Initial release.
