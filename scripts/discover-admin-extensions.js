#!/usr/bin/env node
/**
 * Discover module admin extensions.
 *
 * Scans src/admin/modules/{slug}/index.{ts,tsx} and writes a static
 * manifest at src/admin/.generated/discovered.ts. Each match becomes
 * a top-level import + an entry in the exported `discovered` map,
 * keyed by the directory name (which equals the module slug).
 *
 * Static imports — not require.context — keep the produced bundle
 * tree-shakable and the manifest type-checkable. Webpack invokes this
 * script through a plugin in webpack.admin.js before each build and
 * watch tick, so adding/removing a module's index.tsx is reflected
 * automatically.
 *
 * The output file is .gitignore'd; this script is the single source
 * of truth for what's in it.
 */

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const MODULES_DIR = path.join(ROOT, 'src', 'admin', 'modules');
const OUTPUT_DIR = path.join(ROOT, 'src', 'admin', '.generated');
const OUTPUT_FILE = path.join(OUTPUT_DIR, 'discovered.ts');
const ENTRY_EXTENSIONS = ['.tsx', '.ts'];

function findModuleEntries() {
    if (!fs.existsSync(MODULES_DIR)) {
        return [];
    }

    return fs
        .readdirSync(MODULES_DIR, { withFileTypes: true })
        .filter((d) => d.isDirectory() && !d.name.startsWith('.'))
        .map((d) => {
            const slug = d.name;
            const dir = path.join(MODULES_DIR, slug);
            const entry = ENTRY_EXTENSIONS.map((ext) => path.join(dir, 'index' + ext)).find((p) =>
                fs.existsSync(p),
            );
            return entry === undefined ? null : { slug, entry };
        })
        .filter((e) => e !== null)
        .sort((a, b) => a.slug.localeCompare(b.slug));
}

function renderManifest(entries) {
    const header =
        '/**\n' +
        ' * AUTO-GENERATED — do not edit.\n' +
        ' *\n' +
        ' * Written by scripts/discover-admin-extensions.js on every build\n' +
        ' * and watch tick. Edit src/admin/modules/{slug}/index.tsx to\n' +
        ' * change what appears here.\n' +
        ' */\n' +
        "import type { ModuleExtension } from '../types';\n";

    if (entries.length === 0) {
        return (
            header +
            '\n' +
            'export const discovered: Record<string, ModuleExtension> = {};\n'
        );
    }

    const imports = entries
        .map((e, i) => {
            const importPath = '../modules/' + e.slug + '/index';
            return "import mod" + i + " from '" + importPath + "';";
        })
        .join('\n');

    const mapEntries = entries
        .map((e, i) => "    '" + e.slug + "': mod" + i + ',')
        .join('\n');

    return (
        header +
        '\n' +
        imports +
        '\n\n' +
        'export const discovered: Record<string, ModuleExtension> = {\n' +
        mapEntries +
        '\n};\n'
    );
}

function writeIfChanged(filePath, contents) {
    fs.mkdirSync(path.dirname(filePath), { recursive: true });
    const previous = fs.existsSync(filePath) ? fs.readFileSync(filePath, 'utf8') : '';
    if (previous === contents) {
        return false;
    }
    fs.writeFileSync(filePath, contents);
    return true;
}

function run() {
    const entries = findModuleEntries();
    const manifest = renderManifest(entries);
    const changed = writeIfChanged(OUTPUT_FILE, manifest);
    if (changed) {
        const rel = path.relative(ROOT, OUTPUT_FILE);
        const summary = entries.length === 0 ? 'no extensions' : entries.map((e) => e.slug).join(', ');
        console.log('[discover-admin-extensions] wrote ' + rel + ' (' + summary + ')');
    }
    return entries;
}

if (require.main === module) {
    run();
}

module.exports = { run };
