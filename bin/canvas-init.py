#!/usr/bin/env python3
"""Canvas initialization / rename script.

Turns the Canvas scaffold into your own plugin by replacing every Canvas
identifier (namespace, text domain, prefixes, slugs, REST namespace, store
names, JS globals, capabilities) across the codebase, renaming the main plugin
file, and then deleting itself.

Designed to be driven non-interactively (e.g. by an AI agent) via flags, but it
also prompts when run by hand. It requires nothing but Python 3.8+ — no
WordPress, Composer, or npm — so it can run before anything is installed.

Usage (non-interactive):
    python3 bin/canvas-init.py --name "My Plugin" --yes

    python3 bin/canvas-init.py \\
        --name "My Plugin" --slug my-plugin --namespace My_Plugin \\
        --prefix my_plugin --js myPlugin --author "Jane Doe" \\
        --email jane@example.com --uri https://example.com/my-plugin \\
        --author-uri https://example.com --desc "My plugin description." --yes

Usage (interactive):
    python3 bin/canvas-init.py
"""

from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path

# Directories to skip entirely.
SKIP_DIRS = {".git", "node_modules", "vendor", "build", "languages"}

# Only rewrite files with these extensions (everything that can contain a
# Canvas identifier in this scaffold).
EXTENSIONS = {
    ".php", ".js", ".jsx", ".ts", ".tsx", ".json", ".scss", ".sass", ".css",
    ".md", ".txt", ".xml", ".dist", ".neon", ".yml", ".yaml", ".html",
    ".pot", ".po",
}

ROOT = Path(__file__).resolve().parent.parent
SELF = Path(__file__).resolve()


def slugify(value: str) -> str:
    """Lowercase, hyphenated slug."""
    return re.sub(r"[^a-z0-9]+", "-", value.strip().lower()).strip("-")


def to_namespace(slug: str) -> str:
    """PascalCase, underscore-separated namespace from a slug."""
    return "_".join(part.capitalize() for part in slug.split("-") if part)


def to_camel(slug: str) -> str:
    """camelCase identifier from a slug."""
    parts = [p for p in slug.split("-") if p]
    if not parts:
        return "plugin"
    return parts[0] + "".join(p.capitalize() for p in parts[1:])


def prompt(label: str, default: str = "") -> str:
    """Prompt for a value, returning the default on empty input."""
    suffix = f" [{default}]" if default else ""
    try:
        answer = input(f"  {label}{suffix}: ").strip()
    except EOFError:
        answer = ""
    return answer or default


def confirm(label: str) -> bool:
    """Yes/no confirmation (defaults to yes)."""
    try:
        answer = input(f"{label} [Y/n]: ").strip().lower()
    except EOFError:
        answer = ""
    return answer in ("", "y", "yes")


def build_replacements(values: dict) -> dict:
    """Build the ordered token map.

    The single-pass replacement (see ``apply``) tries longer keys first, so the
    explicit ``namespace Canvas;`` / ``Canvas\\`` / capability keys win over the
    bare ``Canvas`` (display name) and ``canvas`` (slug) keys in code contexts.
    """
    name = values["name"]
    slug = values["slug"]
    namespace = values["namespace"]
    prefix = values["prefix"]
    const = prefix.upper()
    js = values["js"]

    replacements: dict[str, str] = {}

    # Optional metadata (only when provided).
    if values.get("author"):
        replacements["Your Name"] = values["author"]
    if values.get("email"):
        replacements["your.email@example.com"] = values["email"]
    if values.get("uri"):
        replacements["https://example.com/canvas"] = values["uri"]
    if values.get("author_uri"):
        replacements["https://example.com"] = values["author_uri"]
    if values.get("desc"):
        replacements["A starter framework for WordPress plugins with React admin UI."] = values["desc"]
        replacements["Canvas - WordPress Plugin Starter Framework"] = values["desc"]

    # Identifier tokens.
    replacements.update({
        "canvasData": js + "Data",
        "canvas/": slug + "/",
        "namespace Canvas;": "namespace " + namespace + ";",
        "Canvas\\": namespace + "\\",
        "@package Canvas": "@package " + namespace,
        "manage_canvas": "manage_" + prefix,
        "edit_canvas_content": "edit_" + prefix + "_content",
        "view_canvas": "view_" + prefix,
        "CANVAS_": const + "_",
        "canvas_": prefix + "_",
        "canvas-": slug + "-",
        "Canvas": name,
        "canvas": slug,
    })

    return replacements


def apply(content: str, regex: "re.Pattern[str]", replacements: dict) -> str:
    """Single-pass replacement: longest matching key at each position wins, and
    replacement output is never re-scanned (mirrors PHP's strtr())."""
    return regex.sub(lambda m: replacements[m.group(0)], content)


def collect_files() -> list[Path]:
    """Files eligible for rewriting (excluding skipped dirs and this script)."""
    files = []
    for path in ROOT.rglob("*"):
        if any(part in SKIP_DIRS for part in path.relative_to(ROOT).parts):
            continue
        if not path.is_file() or path.resolve() == SELF:
            continue
        if path.suffix.lower() in EXTENSIONS:
            files.append(path)
    return files


def gather_values(args: argparse.Namespace, interactive: bool) -> dict:
    """Resolve all inputs from flags, with prompts/derivation as needed."""
    name = (args.name or (prompt('Plugin name (e.g. "My Plugin")') if interactive else "")).strip()
    if not name:
        sys.exit("A plugin name is required (pass --name or answer the prompt).")

    slug_default = slugify(name)
    slug = args.slug or (prompt("Slug / text domain", slug_default) if interactive else slug_default)
    slug = slugify(slug) or slug_default

    ns_default = to_namespace(slug)
    namespace = args.namespace or (prompt("PHP namespace", ns_default) if interactive else ns_default)

    prefix_default = slug.replace("-", "_")
    prefix = args.prefix or (prompt("Function / option / table prefix", prefix_default) if interactive else prefix_default)

    js_default = to_camel(slug)
    js = args.js or (prompt("JS global variable", js_default) if interactive else js_default)

    return {
        "name": name,
        "slug": slug,
        "namespace": namespace or ns_default,
        "prefix": prefix or prefix_default,
        "js": js or js_default,
        "author": args.author or (prompt("Author name (optional)") if interactive else ""),
        "email": args.email or (prompt("Author email (optional)") if interactive else ""),
        "uri": args.uri or (prompt("Plugin URI (optional)") if interactive else ""),
        "author_uri": args.author_uri or (prompt("Author URI (optional)") if interactive else ""),
        "desc": args.desc or (prompt("Description (optional)") if interactive else ""),
    }


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Rename the Canvas scaffold to your own plugin.",
        formatter_class=argparse.ArgumentDefaultsHelpFormatter,
    )
    parser.add_argument("--name", help='Display name, e.g. "My Plugin"')
    parser.add_argument("--slug", help="Slug / text domain (default: derived from name)")
    parser.add_argument("--namespace", help="PHP namespace (default: derived from slug)")
    parser.add_argument("--prefix", help="Function/option/table prefix (default: derived from slug)")
    parser.add_argument("--js", help="JS global variable (default: derived from slug)")
    parser.add_argument("--author", help="Author name")
    parser.add_argument("--email", help="Author email")
    parser.add_argument("--uri", help="Plugin URI")
    parser.add_argument("--author-uri", dest="author_uri", help="Author URI")
    parser.add_argument("--desc", help="Plugin description")
    parser.add_argument("-y", "--yes", action="store_true", help="Skip prompts and confirmation")
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    interactive = not args.yes

    print("\n  Canvas -> your plugin")
    print("  ---------------------\n")

    values = gather_values(args, interactive)

    const = values["prefix"].upper()
    print("\n  This will rewrite the project in place:\n")
    print(f"    Display name   {values['name']}")
    print(f"    Slug / domain  {values['slug']}")
    print(f"    Namespace      {values['namespace']}\\")
    print(f"    Prefix         {values['prefix']}_ / {const}_")
    print(f"    JS global      {values['js']}Data")
    print(f"    Main file      {values['slug']}.php\n")

    if interactive and not confirm("  Proceed?"):
        print("  Aborted. Nothing was changed.")
        return 0

    replacements = build_replacements(values)
    # Longest keys first so the regex prefers the most specific token at each spot.
    regex = re.compile("|".join(re.escape(k) for k in sorted(replacements, key=len, reverse=True)))

    changed = 0
    for path in collect_files():
        try:
            original = path.read_text(encoding="utf-8")
        except (UnicodeDecodeError, OSError):
            continue
        updated = apply(original, regex, replacements)
        if updated != original:
            path.write_text(updated, encoding="utf-8")
            changed += 1

    main_old = ROOT / "canvas.php"
    main_new = ROOT / f"{values['slug']}.php"
    if main_old.is_file() and main_old != main_new:
        main_old.rename(main_new)

    print(f"\n  Rewrote {changed} file(s); main file is now {values['slug']}.php")

    # Self-destruct.
    try:
        SELF.unlink()
        SELF.parent.rmdir()  # remove bin/ if now empty
    except OSError:
        pass

    print("\n  Done. Next steps:")
    print("    1. composer install")
    print("    2. npm install && npm run build")
    print("    3. Activate the plugin in WordPress.\n")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
