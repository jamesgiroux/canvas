"""Rename invariants for bin/canvas-init.py.

The kit's CSS custom properties and class names rename correctly because the
ordered `canvas-` rule matches inside them. That is emergent, not declared, so
it is asserted here: a broad new rule that over-matches would break the
@wordpress/components re-anchor silently, at style time, in someone else's
plugin.
"""

import unittest
import importlib.util
import re
from pathlib import Path

SCRIPT = Path(__file__).resolve().parents[2] / "bin" / "canvas-init.py"

if not SCRIPT.is_file():
    raise unittest.SkipTest(
        "canvas-init.py has already initialized the scaffold and self-deleted; "
        "rename invariants no longer apply."
    )

spec = importlib.util.spec_from_file_location("canvas_init", SCRIPT)
canvas_init = importlib.util.module_from_spec(spec)
spec.loader.exec_module(canvas_init)

# WordPress's own custom properties. The kit re-points these at the plugin
# accent; they are not ours to rename.
WP_VARIABLES = [
    "--wp-admin-theme-color",
    "--wp-admin-theme-color-darker-10",
    "--wp-admin-theme-color-darker-20",
    "--wp-admin-border-width-focus",
    "--wp-components-color-accent",
    "--wp-components-color-accent-darker-10",
    "--wp-components-color-accent-darker-20",
]


class RenameTest(unittest.TestCase):
    def setUp(self):
        self.replacements = canvas_init.build_replacements(
            {
                "name": "Acme Widgets",
                "slug": "acme-widgets",
                "namespace": "Acme_Widgets",
                "prefix": "acme_widgets",
                "const": "ACME_WIDGETS",
                "js": "acmeWidgets",
            }
        )
        pattern = "|".join(
            re.escape(key)
            for key in sorted(self.replacements, key=len, reverse=True)
        )
        self.regex = re.compile(pattern)

    def rename(self, text):
        return canvas_init.apply(text, self.regex, self.replacements)

    def test_kit_tokens_are_renamed(self):
        self.assertEqual(
            self.rename("--canvas-accent: #0675c4;"),
            "--acme-widgets-accent: #0675c4;",
        )
        self.assertEqual(
            self.rename("box-shadow: var(--canvas-shadow-hi);"),
            "box-shadow: var(--acme-widgets-shadow-hi);",
        )

    def test_kit_classes_are_renamed(self):
        self.assertEqual(
            self.rename(".canvas-page-title em {"),
            ".acme-widgets-page-title em {",
        )

    def test_submenu_body_selector_survives_multiword_slug_rename(self):
        renamed_substring = self.rename("_page_canvas")
        self.assertEqual(renamed_substring, "_page_acme-widgets")
        self.assertIn(
            renamed_substring,
            "acme-widgets_page_acme-widgets-items",
        )

    def test_wordpress_variables_survive_untouched(self):
        for variable in WP_VARIABLES:
            source = f"\t{variable}: var(--canvas-accent);"
            result = self.rename(source)
            self.assertIn(
                variable,
                result,
                f"{variable} was rewritten; the @wordpress/components re-anchor is broken",
            )

    def test_tokens_json_prefix_is_renamed(self):
        self.assertEqual(
            self.rename('"prefix": "canvas",'),
            '"prefix": "acme-widgets",',
        )

    def test_python_sources_and_design_skill_paths_are_renameable(self):
        self.assertTrue({".py", ".mjs"}.issubset(canvas_init.EXTENSIONS))
        self.assertEqual(
            self.rename("canvas-design-kit"),
            "acme-widgets-design-kit",
        )


if __name__ == "__main__":
    unittest.main()
