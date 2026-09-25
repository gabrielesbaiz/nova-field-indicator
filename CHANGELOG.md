# Changelog

All notable changes to `nova-field-indicator` will be documented in this file.

## 3.0.0 - 2026-09-25

A full rewrite. See [UPGRADE.md](UPGRADE.md) before upgrading — and run
`php artisan nova-field-indicator:upgrade` to migrate your call sites.

### Requirements

- Requires PHP 8.3+, Laravel 12 or 13, and Nova 5.7+ (Nova 6 is explicitly conflicted).
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
  they reach an inline style. The 2.x client-side regex was unanchored and would have
  passed `#fff; background-image:url(…)` straight through.
- `whitespace-no-wrap` was Tailwind v1 syntax and had done nothing since Nova 4.
- The neutral colour no longer depends on stylesheet source order: 2.x always emitted
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
- `php artisan nova-field-indicator:upgrade` to migrate from 2.x.
- Real test coverage: 164 Pest tests and 22 Vitest tests, PHPStan level 6.

### Changed

- **Labels and colours are resolved on the server.** 2.x pushed the whole configuration
  into every row of an index and looked it up again in the browser; the payload now
  carries one resolved indicator per value.
- Colours resolve to Nova's own `--colors-<family>-<shade>` variables, so re-theming
  Nova re-themes every indicator. The built-in names now match the Tailwind 3 palette.
- The field is `Unfillable` and hidden on every form view, including attach and
  update-attached, which the old `$showOnCreation`/`$showOnUpdate` pair missed.
- Vite 6 replaces Laravel Mix. Output paths are unchanged.
- The Vue component is now `nova-field-indicator`.
- The bundle is 1.09 kB gzipped, down from 1.4 kB, despite the added features.

### Removed

- `shouldHide()`, `shouldHideIfNo()` and `withoutLabels()` — see [UPGRADE.md](UPGRADE.md).
- The `.indicator-*` CSS classes.
- The `FormField` component, which was registered and bundled but unreachable.
- `spatie/laravel-package-tools`, which was required but never used.
- Fifteen npm dependencies, six of them (leaflet, vuex, inertia, scriptjs and friends)
  copy-pasted from `nova-field-map` and never referenced.

## 1.0.0

- Initial release.
