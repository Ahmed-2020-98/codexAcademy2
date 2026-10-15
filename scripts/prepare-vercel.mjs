// Tailwind's Filament theme imports CSS and templates from Composer packages.
// Install them before Vite, using the same PHP toolchain as the pinned runtime.
if (process.env.VERCEL) {
    const { getPhpFiles, runComposerInstall } = await import('vercel-php/dist/utils.js');
    await getPhpFiles();
    await runComposerInstall(process.cwd());
}
