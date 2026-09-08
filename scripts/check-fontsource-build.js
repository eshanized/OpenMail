import { readFileSync, existsSync } from 'node:fs';
import { resolve } from 'node:path';

const manifestPath = resolve('public/build/manifest.json');

function fail(message) {
    console.error(`FAIL: ${message}`);
    process.exit(1);
}

function ok(message) {
    console.log(`OK: ${message}`);
    process.exit(0);
}

if (!existsSync(manifestPath)) {
    fail('public/build/manifest.json not found — run `npm run build` first');
}

let manifest;
try {
    const content = readFileSync(manifestPath, 'utf-8');
    manifest = JSON.parse(content);
} catch (e) {
    fail(`Failed to parse manifest.json: ${e instanceof Error ? e.message : String(e)}`);
}

const entries = Object.values(manifest);

const hasAppCss = entries.some(e => e.name === 'app.css' || e.src === 'resources/css/app.css');
const hasAppJs = entries.some(e => e.name === 'app' && e.src === 'resources/js/app.js');
const hasFontsource = entries.some(e =>
    e.file.includes('fontsource') || e.file.includes('jakarta') || e.file.includes('plus-jakarta')
);

if (!hasAppCss) {
    fail('app.css entry missing from manifest.json');
}

if (!hasAppJs) {
    fail('app.js entry missing from manifest.json');
}

if (!hasFontsource) {
    fail('Fontsource (Plus Jakarta Sans) entry missing from manifest.json — font not yet installed or built');
}

ok('Manifest contains app.css, app.js, and fontsource entries');