import { execFileSync } from 'node:child_process';
import { randomUUID } from 'node:crypto';
import { readFileSync, unlinkSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const assets = new URL('../../site/assets/', import.meta.url);

function minify(args) {
    const command = ['pnpm', 'dlx', 'esbuild@0.24.0', '--minify', '--log-level=warning', ...args];
    const options = {
        cwd: fileURLToPath(assets),
        stdio: 'inherit',
    };
    if (process.platform === 'win32') {
        // pnpm commonly uses a .cmd shim. Filenames are fixed or generated UUIDs.
        execFileSync(process.env.ComSpec || 'cmd.exe', ['/d', '/s', '/c', command.join(' ')], options);
        return;
    }
    execFileSync(command[0], command.slice(1), options);
}

const siteCss = Buffer.concat([
    readFileSync(new URL('css/fonts.css', assets)),
    readFileSync(new URL('css/site.css', assets)),
]);
const inputName = `.rfws-build-${randomUUID()}.css`;
const inputFile = new URL(inputName, assets);
writeFileSync(inputFile, siteCss, { flag: 'wx' });
try {
    minify([inputName, '--outfile=css/site.min.css']);
} finally {
    unlinkSync(inputFile);
}
minify(['css/portal.css', '--outfile=css/portal.min.css']);
minify(['js/site.js', '--target=es2017', '--outfile=js/site.min.js']);
minify(['js/portal.js', '--target=es2017', '--outfile=js/portal.min.js']);
