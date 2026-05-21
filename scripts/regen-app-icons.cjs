/**
 * Regenerate apple-touch-icon and android-chrome icons so they have NO
 * transparent edges. iOS, when a user taps "Add to Home Screen", paints
 * white behind any transparent pixels in apple-touch-icon.png — which is
 * what users were seeing as a "white border" around the RadarLeb crest.
 *
 * Source: public/assets/imgs/logo.png (the high-res Lebanese-flag
 * crosshair crest). We trim its alpha-channel padding, then composite it
 * onto a solid brand-navy square so the resulting icon fills its bounding
 * box edge to edge.
 *
 * Run with: node scripts/regen-app-icons.js
 */
const path = require('path');
const sharp = require('sharp');

// Use the existing square apple-touch icon as the source (the wide
// logo.png in /assets/imgs/ also contains the "RADAR LEB" wordmark which
// would shrink the crest to an unreadable thumbnail at icon sizes). We
// just need to fill the transparent corners with brand navy so iOS stops
// painting white behind them.
const SRC = path.resolve(__dirname, '..', 'public', 'apple-touch-icon.png');
const PUBLIC = path.resolve(__dirname, '..', 'public');

const BG = { r: 6, g: 33, b: 46, alpha: 1 }; // #06212e — matches site.webmanifest / theme-color

async function buildIcon(outName, size) {
    // The source icon already has a small transparent gutter around the
    // crest. Don't trim — we want to preserve its proportions so the
    // composited crest doesn't kiss the rounded corners on iOS. Just
    // upscale/downscale the whole square into the target box.
    const inner = size;

    const trimmed = await sharp(SRC)
        .resize(inner, inner, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } })
        .toBuffer();

    await sharp({
        create: {
            width: size,
            height: size,
            channels: 4,
            background: BG,
        },
    })
        .composite([{ input: trimmed, gravity: 'center' }])
        .png()
        .toFile(path.join(PUBLIC, outName));

    console.log(`wrote ${outName} (${size}x${size})`);
}

(async () => {
    await buildIcon('apple-touch-icon.png', 180);
    await buildIcon('android-chrome-192x192.png', 192);
    await buildIcon('android-chrome-512x512.png', 512);
})().catch((err) => {
    console.error(err);
    process.exit(1);
});
