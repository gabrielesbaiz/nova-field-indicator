# Security Policy

## Supported versions

| Version | Supported |
|---|---|
| 2.x | ✅ |
| 1.x | ❌ |

## Reporting a vulnerability

Please report security issues to gabriele@sbaiz.com rather than opening a public
issue. You will get a response as quickly as possible.

## Threat model for this package

This field renders a colour-coded mark. It never renders HTML, never uses
`v-html`, and never echoes an attribute value as markup — so the usual stored-XSS
surface of a Nova field does not apply here.

The one boundary that does exist is **CSS**, because a configured colour ends up
in an inline custom property:

- Colour values are validated **server-side** by `Support\ColorResolver` against
  an anchored allow-list, before they are ever serialized. Only `#hex`,
  `rgb()`/`rgba()`, `hsl()`/`hsla()`, `oklch()`, `lab()`/`lch()`, `color-mix()`
  and `var(--custom-property)` survive it, capped at 64 characters.
- Anything containing `;`, `{`, `}`, `/*`, `\`, `<`, `>`, `url(`, `expression(`,
  `image-set(` or `javascript:` is rejected with an exception. `url()` is the
  construct that matters most: it is the historical exfiltration vector for a
  value that lands in a CSS property.
- The Vue component does **no** pattern matching on colours at all. It binds a
  style *object*, which Vue applies through `CSSStyleDeclaration.setProperty()`,
  so an unparseable value is dropped by the browser rather than concatenated
  into a declaration string.

This is a change from 1.x, which classified colours in the browser with an
unanchored regex and interpolated the result into a `background:${color};`
string. That would have accepted `#fff; background-image:url(//example.com)`.

Colour configuration normally comes from your own resource files rather than
from user input, so this is defence in depth rather than a live exploit path.
It is enforced anyway, and `tests/ColorResolverTest.php` covers the payloads.

## Nova credentials

`auth.json` holds your Nova licence credentials. It is listed in `.gitignore`
and marked `export-ignore` in `.gitattributes`, so it is neither committed nor
shipped in a distributed tarball. If you fork this package, verify both before
pushing.
