#!/usr/bin/env php
<?php

/**
 * Prepares an original mascot image (a transparent PNG from resources/mascot/originals/)
 * for the app. Always writes a web-ready version of the whole pose; with --badge it
 * also writes a square badge version.
 *
 *   php scripts/process-mascot-image.php <name> [--badge=<badge key>] [--crop=x,y,w,h] [--fade=<px>] [--fadeleft=<px>]
 *
 * <name>          file name without .png in resources/mascot/originals/
 * --badge=KEY     also write public/images/badges/KEY.png (512 x 512)
 * --crop=x,y,w,h  region of the original to use for the badge (default: the whole
 *                 figure). A head-and-shoulders crop reads much better in a 64 px medal.
 * --fade=PX       soften the bottom PX pixels (of the original) of the badge crop into
 *                 transparency, so a cut through the body does not show a hard edge
 * --fadeleft=PX   the same for the left edge (a cut through a tail or an arm)
 *
 * The originals carry invisible pixels with stray colours (and a faint glow). When such
 * an image is scaled down, those colours bleed into the edge as a dark rim. So almost
 * transparent pixels are cleared and given a neutral orange before anything is resized.
 * Needs the PHP GD extension.
 */
$root = dirname(__DIR__);

const WEB_LONG_SIDE = 720;
const BADGE_SIZE = 512;
const CLEAR_BELOW_OPACITY = 0.06;

$args = array_slice($argv, 1);
$name = array_shift($args);

if (! $name || str_starts_with($name, '--')) {
    fwrite(STDERR, "Usage: php scripts/process-mascot-image.php <name> [--badge=KEY] [--crop=x,y,w,h] [--fade=PX]\n");
    exit(1);
}

$options = [];
foreach ($args as $arg) {
    if (preg_match('/^--([a-z]+)=(.+)$/', $arg, $m)) {
        $options[$m[1]] = $m[2];
    }
}

$source = "{$root}/resources/mascot/originals/{$name}.png";
if (! is_file($source)) {
    fwrite(STDERR, "Original not found: {$source}\n");
    exit(1);
}

$image = imagecreatefrompng($source);
imagealphablending($image, false);
imagesavealpha($image, true);
$width = imagesx($image);
$height = imagesy($image);

// 1) Clear almost-transparent pixels and give them a neutral edge colour.
$clear = imagecolorallocatealpha($image, 0xD2, 0x60, 0x1A, 127);
$minX = $width;
$minY = $height;
$maxX = 0;
$maxY = 0;

for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        $alpha = (imagecolorat($image, $x, $y) >> 24) & 127;
        $opacity = 1 - $alpha / 127;

        if ($opacity < CLEAR_BELOW_OPACITY) {
            imagesetpixel($image, $x, $y, $clear);
        } elseif ($opacity >= 0.5) {
            $minX = min($minX, $x);
            $maxX = max($maxX, $x);
            $minY = min($minY, $y);
            $maxY = max($maxY, $y);
        }
    }
}

function transparentCanvas(int $w, int $h)
{
    $canvas = imagecreatetruecolor($w, $h);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0xD2, 0x60, 0x1A, 127));

    return $canvas;
}

function save($image, string $path): void
{
    @mkdir(dirname($path), 0775, true);
    imagesavealpha($image, true);
    imagepng($image, $path, 9);
    printf("%s  %dx%d  %d KB\n", str_replace(dirname(__DIR__).'/', '', $path), imagesx($image), imagesy($image), filesize($path) / 1024);
}

// 2) Web-ready version of the whole pose: cropped to the figure, long side 720 px.
$pad = 8;
$cropX = max(0, $minX - $pad);
$cropY = max(0, $minY - $pad);
$cropW = min($width, $maxX + $pad + 1) - $cropX;
$cropH = min($height, $maxY + $pad + 1) - $cropY;
$scale = WEB_LONG_SIDE / max($cropW, $cropH);
$outW = (int) round($cropW * $scale);
$outH = (int) round($cropH * $scale);

$web = transparentCanvas($outW, $outH);
imagecopyresampled($web, $image, 0, 0, $cropX, $cropY, $outW, $outH, $cropW, $cropH);
save($web, "{$root}/public/images/mascot/{$name}.png");

// 3) Optional square badge version.
if (isset($options['badge'])) {
    if (isset($options['crop'])) {
        [$bx, $by, $bw, $bh] = array_map('intval', explode(',', $options['crop']));
    } else {
        [$bx, $by, $bw, $bh] = [$cropX, $cropY, $cropW, $cropH];
    }

    $side = max($bw, $bh);
    $square = transparentCanvas($side, $side);
    // Centre the crop in the square on both axes (a wide crop like a running pose
    // would otherwise sit at the top of the medal with empty space below).
    $offsetX = intdiv($side - $bw, 2);
    $offsetY = intdiv($side - $bh, 2);
    imagecopy($square, $image, $offsetX, $offsetY, $bx, $by, $bw, $bh);

    $badge = transparentCanvas(BADGE_SIZE, BADGE_SIZE);
    imagecopyresampled($badge, $square, 0, 0, 0, 0, BADGE_SIZE, BADGE_SIZE, $side, $side);

    if (isset($options['fade']) && (int) $options['fade'] > 0) {
        $fadeRows = (int) round((int) $options['fade'] * BADGE_SIZE / $side);
        $cutLine = (int) round(($offsetY + $bh) * BADGE_SIZE / $side);   // where the crop ends in the badge
        for ($y = max(0, $cutLine - $fadeRows); $y < BADGE_SIZE; $y++) {
            $factor = $y >= $cutLine ? 0.0 : ($cutLine - $y) / $fadeRows;
            for ($x = 0; $x < BADGE_SIZE; $x++) {
                $rgba = imagecolorat($badge, $x, $y);
                $alpha = ($rgba >> 24) & 127;
                if ($alpha === 127) {
                    continue;
                }
                $opacity = (1 - $alpha / 127) * $factor;
                imagesetpixel($badge, $x, $y, ($rgba & 0xFFFFFF) | ((int) round((1 - $opacity) * 127) << 24));
            }
        }
    }

    if (isset($options['fadeleft']) && (int) $options['fadeleft'] > 0) {
        $fadeCols = (int) round((int) $options['fadeleft'] * BADGE_SIZE / $side);
        $cutColumn = (int) round($offsetX * BADGE_SIZE / $side);   // where the crop starts in the badge
        for ($x = $cutColumn; $x < min(BADGE_SIZE, $cutColumn + $fadeCols); $x++) {
            $factor = ($x - $cutColumn) / $fadeCols;
            for ($y = 0; $y < BADGE_SIZE; $y++) {
                $rgba = imagecolorat($badge, $x, $y);
                $alpha = ($rgba >> 24) & 127;
                if ($alpha === 127) {
                    continue;
                }
                $opacity = (1 - $alpha / 127) * $factor;
                imagesetpixel($badge, $x, $y, ($rgba & 0xFFFFFF) | ((int) round((1 - $opacity) * 127) << 24));
            }
        }
        // Everything left of the crop is empty already (the square is padded transparently).
    }

    save($badge, "{$root}/public/images/badges/{$options['badge']}.png");
}
