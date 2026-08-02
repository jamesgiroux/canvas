# Design

> Fill in: what this plugin's admin is for, and who buys it.

Use this file as the plugin's visual contract. Fill in the product prompts, then
keep the type roles, color discipline, depth, motion, accessibility floor,
token vocabulary, and anti-references intact unless the product has a documented
reason to change them.

## Shell

> Fill in: define this plugin's information architecture, navigation groups,
> primary synthesis surface, drill-down surfaces, and action boundary. State
> what the interface helps a user decide and which actions belong elsewhere.

The shell is a full-page admin takeover that follows the block editor's frame:
a near-black sidebar, a rounded light content panel, and hidden wp-admin chrome.
Keep the shell native, trustworthy, and calm. Product character belongs in the
content surfaces, not in ornamental navigation.

## Product vocabulary

> Fill in: name the product's actors, objects, states, and actions. Record words
> the interface must use and near-synonyms it must not use.

## Editorial language

The interface should read as if a person with taste authored it. Aim for
precision without coldness. The signature is a disciplined display face broken
once per hero by a serif italic. The interruption is the whole thesis made
visible.

### Typography

Four roles, each with one job:

- **Display** (`--canvas-font-display`): page titles, card titles, and large KPI
  numbers. Use tight tracking and semibold weight.
- **Serif accent** (`--canvas-font-serif`): exactly one word in a hero headline,
  and one hero per surface. Never add a second accent word.
- **Mono** (`--canvas-font-mono`): eyebrows, micro-labels, section labels, and
  small tabular data. Uppercase eyebrows use deliberate letter spacing.
- **Body** (`--canvas-font-body`): prose, help text, and control labels. This
  keeps the interface grounded in wp-admin.

Use `tabular-nums` on every number, including KPIs, table cells, deltas, counts,
and axis labels. Numbers must not reflow when they animate.

## Adding type

The kit declares four font roles and ships no font files, so `--canvas-font-display`
falls back to the system sans and `--canvas-font-serif` to Georgia italic. To give
the plugin its own voice, self-host OFL faces under `src/assets/fonts/`, declare
them with `@font-face` at the top of `_tokens.scss`, and point the role at them.
Keep the roles: the contract is display, serif accent, mono, body, whatever the
faces are.

Do not use Fraunces, Geist, Inter, or Space Grotesk. They are the over-converged
AI-default faces and they will make considered work look generated.

### Color and data

- **Monochrome data plus one accent.** Charts, sparklines, and comparison series
  use neutral grays. `--canvas-accent` marks the one series or value that matters.
  The accent is for primary actions, current selection, the primary data series,
  and live state. It is never decoration.
- **Warm sunken zones.** Grouped and inset areas use
  `--canvas-surface-tint`, not flat white. This creates quiet rhythm without
  adding boxes.
- **Hairline dividers.** Use `--canvas-hair` for dividers and rings. Rows separate
  with a hairline and `--canvas-row-hover`, never zebra striping.
- **Ink hierarchy.** `--canvas-ink` is primary text, `--canvas-ink-2` is
  secondary text, and `--canvas-ink-3` is restricted to large or non-essential
  text.

### Depth

Use tinted, layered shadows with cool blue-gray ink, never pure-black shadows.
The resting tier is `--canvas-shadow`; elevated and feature surfaces use
`--canvas-shadow-hi`. Cards hover by growing the shadow ring, not by translating.

### Components

- **Page header:** optional backlink, mono eyebrow, display title with at most
  one serif-italic accent word, description, and trailing actions.
- **KPI tile:** micro-label, large tabular value, and an optional mono trend with
  a directional glyph. State is never communicated by color alone.
- **Table:** accessible table markup with hairline-separated rows, tabular data,
  and a quiet hover tint. Do not add zebra striping.
- **Badge:** a tinted pill with default, success, warning, error, and info
  variants. Text supplies the meaning; color supports it.
- **Tabs:** a segmented, keyboard-operable control with a raised selection on a
  sunken track.
- **Card help:** a small information affordance beside a card title. Move
  explanatory prose into its tooltip, but keep honesty captions visible beside
  the data they qualify.

### Motion

Every transition and animation uses the duration and easing registry. Press is
short tactile feedback, hover is a local affordance change, route motion is a
view entrance, reveal is page and data entrance, chart is KPI count-up, ring is
a draw animation, and shimmer is loading feedback.

Avoid animating layout properties such as `width`, `height`, and `padding`.
Collapses should use a grid track. Every moving partial must declare a
`prefers-reduced-motion: reduce` path that reaches the final state immediately.

## Accessibility

The current palette was measured with the WCAG 2.x relative-luminance formula:

| Pair | Ratio |
| --- | ---: |
| `--canvas-ink` on `--canvas-surface` | 16.67:1 |
| `--canvas-ink` on `--canvas-surface-tint` | 15.54:1 |
| `--canvas-ink-2` on `--canvas-surface` | 7.33:1 |
| `--canvas-ink-2` on `--canvas-surface-tint` | 6.83:1 |
| `--canvas-ink-3` on `--canvas-surface` | 4.20:1, large or non-essential text only |
| `--canvas-ink-3` on `--canvas-surface-tint` | 3.91:1, large or non-essential text only |
| `--canvas-on-dark` on `--canvas-bg-frame` | 14.64:1 |
| `--canvas-on-dark` on `--canvas-frame-2` | 12.43:1 |
| `--canvas-on-dark-2` on `--canvas-bg-frame` | 7.14:1 |
| `--canvas-on-dark-2` on `--canvas-frame-2` | 6.07:1 |
| Default badge: `--canvas-ink-2` on `--canvas-surface-tint` | 6.83:1 |
| Success badge: `--canvas-success` on `--canvas-success-tint` | 4.60:1 |
| Warning badge: `--canvas-warning` on `--canvas-warning-tint` | 4.57:1 |
| Error badge: `--canvas-error` on `--canvas-error-tint` | 5.67:1 |
| Info badge: `--canvas-accent-deep` on `--canvas-accent-tint` | 9.25:1 |

The floor is WCAG 2.1 AA: at least 4.5:1 for body text and 3:1 for
large text. `--canvas-ink-3` is not a body-text color. Every control needs a
visible `focus-visible` ring and keyboard operation. State must never rely on
color alone. Tabular numerals prevent animated-number reflow. Follow wp-admin
accessibility patterns.

## Implementation

`tokens.json` is the source of truth. `src/styles/_tokens.scss` mirrors it as
runtime custom properties scoped to the plugin's own body and page roots, which
also lets body-level portals inherit the palette. Change both files together.
`tests/js/tokens.test.mjs` rejects drift, unregistered tokens, unversioned token
sets, literal motion durations, missing reduced-motion collapses, and damage to
WordPress's own variables.

Reuse the existing partial that owns the concern before adding styles:

| Partial | Responsibility |
| --- | --- |
| `src/styles/_tokens.scss` | Runtime token contract and WordPress variable anchors |
| `src/styles/components/_typography.scss` | Type roles, page titles, accents, labels, and tabular utility |
| `src/styles/components/_controls.scss` | Buttons, selects, inputs, textareas, chips, and focus states |
| `src/styles/components/_surfaces.scss` | Cards, sunken zones, empty states, help, and style-guide surfaces |
| `src/styles/components/_data.scss` | Tables, KPI tiles, charts, and sparklines |
| `src/styles/components/_motion.scss` | Reveals, route motion, skeletons, and reduced-motion collapse |
| `src/styles/components/_badge.scss` | Badge variants and sizes |
| `src/styles/components/_error-boundary.scss` | Error-boundary presentation |

Use `@wordpress/components` for native control behavior and accessibility, then
apply the kit through tokens and scoped selectors. Some control overrides depend
on internal class names, including `.components-input-control__backdrop` and
`.components-custom-select-control__item`. Those selectors are coupled to the
installed `@wordpress/components` version. After a major dependency update,
inspect the development style guide and retest keyboard, focus, popover, modal,
and tooltip behavior.

The seven custom properties below belong to WordPress. They are re-anchored in
the kit scope so unstyled WordPress controls use the plugin accent. Never prefix,
rename, or drop them:

| Property | Value in the kit scope |
| --- | --- |
| `--wp-admin-theme-color` | `var(--canvas-accent)` |
| `--wp-admin-theme-color-darker-10` | `var(--canvas-accent-hover)` |
| `--wp-admin-theme-color-darker-20` | `var(--canvas-accent-deep)` |
| `--wp-admin-border-width-focus` | `1px` |
| `--wp-components-color-accent` | `var(--canvas-accent)` |
| `--wp-components-color-accent-darker-10` | `var(--canvas-accent-hover)` |
| `--wp-components-color-accent-darker-20` | `var(--canvas-accent-deep)` |

## Token vocabulary

The table below matches the current declarations in
`src/styles/_tokens.scss`. Do not invent a near-duplicate value in a component
partial. Add a named role to `tokens.json` and this table when the system truly
needs a new token.

### Frame, surfaces, ink, accent, and data

| Token | Value |
| --- | --- |
| `--canvas-bg-frame` | `#1e1e1e` |
| `--canvas-frame-2` | `#2b2b2b` |
| `--canvas-frame-line` | `rgba(255, 255, 255, 0.09)` |
| `--canvas-on-dark` | `#f0f0f1` |
| `--canvas-on-dark-2` | `#a7aaad` |
| `--canvas-on-accent` | `#fff` |
| `--canvas-surface` | `#fff` |
| `--canvas-surface-tint` | `#f7f7f5` |
| `--canvas-surface-muted` | `#f6f7f7` |
| `--canvas-surface-code` | `#f0f0f1` |
| `--canvas-row-hover` | `#fcfcfb` |
| `--canvas-ink` | `#1e1e1e` |
| `--canvas-ink-2` | `#50575e` |
| `--canvas-ink-3` | `#787c82` |
| `--canvas-accent` | `#0675c4` |
| `--canvas-accent-hover` | `#055d9c` |
| `--canvas-accent-deep` | `#044474` |
| `--canvas-accent-tint` | `#f0f6fb` |
| `--canvas-accent-line` | `#c9e0f2` |
| `--canvas-border` | `#dcdcde` |
| `--canvas-border-strong` | `#c3c4c7` |
| `--canvas-data-1` | `#c7ccd1` |
| `--canvas-data-2` | `#dfe3e7` |
| `--canvas-data-3` | `#aeb6bf` |
| `--canvas-neutral-tint` | `#f1f2f3` |
| `--canvas-hair` | `rgba(34, 42, 53, 0.09)` |
| `--canvas-hair-strong` | `rgba(34, 42, 53, 0.14)` |

### Semantic states

| Token | Value |
| --- | --- |
| `--canvas-success` | `#00811d` |
| `--canvas-success-tint` | `#edf7ee` |
| `--canvas-warning` | `#886b00` |
| `--canvas-warning-tint` | `#faf3df` |
| `--canvas-error` | `#b32d2e` |
| `--canvas-error-tint` | `#fcf0f1` |

### Shape and depth

| Token | Value |
| --- | --- |
| `--canvas-radius-lg` | `16px` |
| `--canvas-radius` | `12px` |
| `--canvas-radius-sm` | `8px` |
| `--canvas-radius-pill` | `999px` |
| `--canvas-radius-round` | `50%` |
| `--canvas-shadow` | `0 0 0 1px rgba(34, 42, 53, 0.05), 0 1px 2px rgba(34, 42, 53, 0.04), 0 4px 10px -5px rgba(34, 42, 53, 0.12)` |
| `--canvas-shadow-hi` | `0 0 0 1px rgba(34, 42, 53, 0.07), 0 2px 6px rgba(34, 42, 53, 0.05), 0 16px 34px -12px rgba(19, 19, 22, 0.24)` |
| `--canvas-shadow-control` | `var(--canvas-shadow)` |
| `--canvas-shadow-card` | `var(--canvas-shadow)` |
| `--canvas-shadow-card-hover` | `var(--canvas-shadow-hi)` |
| `--canvas-shadow-popover` | `var(--canvas-shadow-hi)` |
| `--canvas-page-wash` | `linear-gradient(180deg, rgba(217, 236, 249, 0.95) 0%, rgba(232, 243, 251, 0.6) 26%, rgba(240, 246, 251, 0.32) 46%, rgba(255, 255, 255, 0) 74%)` |

### Spacing and layout

| Token | Value |
| --- | --- |
| `--canvas-space-2xs` | `4px` |
| `--canvas-space-xs` | `8px` |
| `--canvas-space-sm` | `12px` |
| `--canvas-space-md` | `16px` |
| `--canvas-space-lg` | `24px` |
| `--canvas-space-xl` | `32px` |
| `--canvas-space-2xl` | `40px` |
| `--canvas-stack-card` | `14px` |
| `--canvas-stack-section` | `40px` |
| `--canvas-control-h` | `40px` |
| `--canvas-sidebar-w` | `300px` |
| `--canvas-gutter` | `14px` |

### Motion and focus

| Token | Value |
| --- | --- |
| `--canvas-duration-press` | `120ms` |
| `--canvas-duration-hover` | `160ms` |
| `--canvas-duration-route` | `150ms` |
| `--canvas-duration-reveal` | `400ms` |
| `--canvas-duration-chart` | `650ms` |
| `--canvas-duration-ring` | `1100ms` |
| `--canvas-duration-shimmer` | `1200ms` |
| `--canvas-duration-reduced` | `1ms` |
| `--canvas-ease` | `cubic-bezier(0.22, 1, 0.36, 1)` |
| `--canvas-ease-out` | `cubic-bezier(0, 0, 0.2, 1)` |
| `--canvas-ease-snap` | `cubic-bezier(0.215, 0.61, 0.355, 1)` |
| `--canvas-focus-ring` | `color-mix(in srgb, var(--canvas-accent) 34%, transparent)` |
| `--canvas-focus-ring-dark` | `rgba(116, 184, 235, 0.72)` |

### Type

| Token | Value |
| --- | --- |
| `--canvas-font-display` | `-apple-system, blinkmacsystemfont, "Segoe UI", sans-serif` |
| `--canvas-font-serif` | `"Iowan Old Style", palatino, georgia, serif` |
| `--canvas-font-mono` | `ui-monospace, "SFMono-Regular", menlo, consolas, monospace` |
| `--canvas-font-body` | `-apple-system, blinkmacsystemfont, "Segoe UI", roboto, helvetica, arial, sans-serif` |

## Anti-references

No flashy-AI ornament such as glow, sparkle, robot, or gradient AI badges; no
generic SaaS cream or purple-gradient dashboard; no rainbow data, use monochrome
plus one accent; no zebra striping; no left-edge accent stripe on a card or row
for emphasis, use `--canvas-accent-tint` fill instead; no pure-black shadows; no
second serif-accent word per hero; nothing that fights wp-admin conventions in
the shell; no wall of controls, use progressive disclosure and whitespace.
