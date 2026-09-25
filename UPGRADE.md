# Upgrade guide

## 2.x → 3.0

### Requirements

| | 2.x | 3.0 |
|---|---|---|
| PHP | ^8.0 | ^8.3 |
| Laravel | 10, 11, 12 | 12, 13 |
| Nova | ^5.0 | ^5.7 (`<6.0`) |

`laravel/nova` is now a runtime requirement rather than a dev dependency.

### Start here

```bash
composer require gabrielesbaiz/nova-field-indicator:^3.0
php artisan nova-field-indicator:upgrade --dry-run
```

Review the output, then run it for real. It is idempotent, so running it twice
is safe. Commit first — it rewrites files in place.

```bash
php artisan nova-field-indicator:upgrade
```

It rewrites the mechanical changes and **reports** everything that needs a
human decision. Read that report; the first entry in it is the one that can
bite silently.

---

### 1. Four colours were invisible and now appear

This is the reason 3.0 exists, and it is a fix that reads as a change.

In 2.x, `success`, `danger`, `warning` and `info` were styled as
`var(--success)`, `var(--danger)` and so on. Those CSS variables existed in
Nova 3 and 4. **Nova 5 does not define them**, so the mark painted with an
invalid colour and rendered completely transparent.

If your resource looks like this:

```php
NovaFieldIndicator::make('Status')->colors([
    'active' => 'success',
    'banned' => 'danger',
])
```

…then on Nova 5 that column has been blank. In 3.0 those dots appear, in green
and red. Nothing is wrong — you are seeing the field work for the first time.

There is no opt-out, and there should not be one.

### 2. Every named colour shifts hue

2.x hard-coded the **Tailwind v1** palette. 3.0 resolves to Nova's own palette
variables, which are Tailwind 3.

| Name | 2.x | 3.0 | Change |
|---|---|---|---|
| `grey` / `gray` | `#B8C2CC` | `gray-400` | cooler, darker |
| `black` | `#22292F` | `gray-900` | darker |
| `red` | `#E3342F` | `red-500` | lighter, less orange |
| `orange` | `#F6993F` | `orange-500` | more saturated |
| `yellow` | `#FFED4A` | `yellow-500` | **noticeably darker** |
| `green` | `#38C172` | `green-500` | similar |
| `teal` | `#4DC0B5` | `teal-500` | more saturated |
| `blue` | `#3490DC` | `blue-500` | more violet |
| `indigo` | `#6574CD` | `indigo-500` | similar |
| `purple` | `#9561E2` | `purple-500` | similar |
| `pink` | `#F66D9B` | `pink-500` | more saturated |

The upgrade command rewrites any of these hexes it finds in a `colors()` call
to the token form.

If you colour-matched a brand and need the exact old value, a literal is
first-class:

```php
->colors(['active' => '#38C172'])
```

Or pin it once, for the whole application, in the published config:

```php
// config/nova-field-indicator.php
'colors' => [
    'tokens' => [
        'green' => '#38C172',
    ],
],
```

### 3. Custom `.indicator-*` CSS no longer applies

2.x invited this, because colours were class names:

```css
/* your Nova stylesheet */
.indicator-brand { background: #123456; }
```

```php
->colors(['active' => 'brand'])
```

3.0 emits no `indicator-*` class at all — colour arrives as an inline custom
property — so those rules are dead and the value falls back to neutral grey.

This is the sharpest real break in the release. It is deliberate: keeping a
legacy hook class would re-create exactly the stylesheet-source-order fragility
being removed. Migrate in one line:

```php
->colors(['active' => '#123456'])
```

…or define `brand` once in the config `colors.tokens` table, as above.

### 4. Renamed and removed methods

| 2.x | 3.0 |
|---|---|
| `->shouldHide($x)` | `->hideWhen($x)` |
| `->shouldHideIfNo()` | `->hideWhenEmpty()` |
| `->withoutLabels()` | **removed** — see below |
| `public $hideCallback` | internal |
| `$showOnCreation` / `$showOnUpdate = false` | implicit |

There are no deprecation shims. A missed call site raises
`BadMethodCallException`, which is a better failure than a silently wrong one.

### 5. `withoutLabels()` is gone — read this one carefully

The names are one character apart and the meanings are nearly opposite, so it
is worth being explicit.

**2.x `withoutLabels()`** meant *"print the raw value instead of a mapped
label"*. In 3.0 that is simply the default: an unmapped value falls back to
itself, exactly as Nova's own `Badge` behaves. So in almost every case you just
delete the call, and the upgrade command does that for you when the chain has
no `->labels()`.

**3.0 `withoutLabel()`**, singular, is a different feature: render the mark with
**no text at all**. Reach for it only if that is genuinely what you want.

If you had `->withoutLabels()` *and* `->labels()` on the same chain, the command
refuses to guess and flags it — that combination was contradictory in 2.x, since
the labels were ignored.

### 6. Value comparison is stricter, in one specific way

`hideWhen()` no longer treats a string argument as a possible callback:

```php
// 2.x: is_callable('trim') was true, so trim() was INVOKED as the predicate.
// 3.0: 'trim' is a value, compared against the attribute.
->hideWhen('trim')
```

The upgrade command reports every `shouldHide()` whose argument names a real PHP
function. If you were relying on the old behaviour, pass a closure:

```php
->hideWhen(fn ($value) => trim($value) === '')
```

Scalar comparison is otherwise unchanged. Both sides are normalised before
comparison, so `0` still matches `'0'` the way it did under 2.x's `==`.

### 7. The field is read-only on forms

It now implements `Unfillable` and calls `exceptOnForms()`, so it is hidden on
create, update, attach and update-attached. The 2.x form component was
registered and bundled but unreachable, and never wrote a value, so nothing can
regress here — but if you forced it on with `->showOnCreating()`, you now get
nothing at all.

Pair it with a real input instead:

```php
Select::make('Status')->options($options)->onlyOnForms(),
NovaFieldIndicator::make('Status')->enum(Status::class)->exceptOnForms(),
```

### 8. The payload changed

Only relevant if you read the serialized field from the API or wrote your own
Vue override.

```jsonc
// 2.x — the whole configuration, repeated on every row
{ "labels": {...}, "colors": {...}, "unknownLabel": "…", "withoutLabels": false, "shouldHide": false }

// 3.0 — one resolved indicator per value
{ "indicators": [{ "value": "active", "label": "Active", "ariaLabel": "Active",
                   "color": { "light": "…", "dark": "…", "soft": "…" },
                   "icon": null, "tooltip": null, "pulse": false }],
  "shape": "dot", "size": "md", "shouldHide": false, "emptyText": "—" }
```

The Vue component is also renamed from `indicator-field` to
`nova-field-indicator`, and the asset handles from `indicator` to
`nova-field-indicator`.

**Build output paths are unchanged** (`dist/js/field.js`, `dist/css/field.css`),
so there is no cache-busting step to worry about.

### 9. Subclasses that override `resolveForDisplay()`

Resolution moved to `jsonSerialize()`. Override `resolveIndicatorFor()` instead —
it is the documented seam, and it receives the normalised value key.

---

## New in 3.0

```php
// One enum instead of three parallel arrays.
NovaFieldIndicator::make('Status')->enum(Status::class)->withIcons(),

// An enum-cast attribute needs no configuration at all.
NovaFieldIndicator::make('Status'),

// Everything about one value in one place.
NovaFieldIndicator::make('Status')->states([
    'active' => ['label' => 'Active', 'color' => 'success', 'icon' => 'check-circle'],
    'syncing' => ['label' => 'Syncing', 'color' => 'sky-400', 'pulse' => true],
]),

// Continuous values.
NovaFieldIndicator::make('Health')->ranges([0 => 'danger', 50 => 'warning', 80 => 'success']),

// Shapes, sizes and an index filter.
NovaFieldIndicator::make('Status')->pill()->size('lg')->filterable(),
```

Publish the config and translations if you want to change the defaults:

```bash
php artisan vendor:publish --tag=nova-field-indicator-config
php artisan vendor:publish --tag=nova-field-indicator-lang
```
