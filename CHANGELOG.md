# Changelog

## 1.1.0

### Added

- A single CSS custom-property token layer that replaces the JavaScript palette, Sass variables, and hardcoded hex values.
- Eight focused SCSS partials for tokens, typography, controls, surfaces, data, motion, badges, and error boundaries.
- Five new design primitives: `PageHeader`, `accentedTitle`, `DataTable`, `CardHelp`, and `Tabs`.
- An animated `MetricCard` with count-up values and reduced-motion support.
- Token parity, motion, reduced-motion, and scaffold rename guard tests.
- A development-only in-admin style guide.
- `DESIGN.md` with the design-system contract and accessibility guidance.
- Eight design skills, including the Canvas design-kit house rules.

### Changed

- The admin shell and component styles now consume the shared token vocabulary.

### Breaking

- `src/constants/design.js` has been removed. Use the CSS custom properties documented in `tokens.json` and `DESIGN.md`.
- `Badge` no longer accepts style overrides through `BADGE_VARIANTS`. Use the variant class contract instead.
