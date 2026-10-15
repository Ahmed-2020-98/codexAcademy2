import { cp, mkdir, rm } from 'node:fs/promises';
import { basename, extname } from 'node:path';

await rm('dist', { recursive: true, force: true });
await mkdir('dist', { recursive: true });
await cp('public', 'dist', {
    recursive: true,
    dereference: false,
    filter: (path) => !basename(path).startsWith('.')
        && basename(path) !== 'storage'
        && basename(path) !== 'hot'
        && extname(path) !== '.php',
});
