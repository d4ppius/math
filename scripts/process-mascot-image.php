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
 * --minarea=N     after removing a baked-in background, also drop loose specks smaller
 *                 than N pixels (e.g. stray speed lines that still carry checker noise)
 *
 * The originals carry invisible pixels with stray colours (and a faint glow). When such
 * an image is scaled down, those colours bleed into the edge as a dark rim. So almost
 * transparent pixels are cleared and given a neutral orange before anything is resized.
 *
 * Some generators export a "transparent" image with a fake white/grey checkerboard baked
 * into fully opaque pixels. If all four corners are opaque, the script treats the image
 * that way and removes the background first: starting from the border it clears every
 * connected pixel that is neutral (grey/white) and bright. Fox fur and cream are warmer,
 * and eyes or the bandana's sign are enclosed by outlines, so the flood cannot reach them.
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

/** Bright and neutral: the two tones of a fake checkerboard (values ~230-255, no colour). */
function isBackgroundPixel(int $rgba): bool
{
    $r = ($rgba >> 16) & 255;
    $g = ($rgba >> 8) & 255;
    $b = $rgba & 255;

    return min($r, $g, $b) >= 215 && (max($r, $g, $b) - min($r, $g, $b)) <= 5;
}

/**
 * Clears a baked-in background: flood fill from the border through bright, neutral pixels.
 * Optionally drops remaining islands smaller than $minArea pixels.
 *
 * @return int number of pixels cleared
 */
function removeBackground($image, int $minArea): int
{
    $w = imagesx($image);
    $h = imagesy($image);
    $clear = imagecolorallocatealpha($image, 0xD2, 0x60, 0x1A, 127);
    $gone = str_repeat("\0", $w * $h);
    $stack = [];

    $seed = function (int $x, int $y) use (&$gone, &$stack, $image, $w): void {
        $i = $y * $w + $x;
        if ($gone[$i] === "\0" && isBackgroundPixel(imagecolorat($image, $x, $y))) {
            $gone[$i] = "\1";
            $stack[] = $i;
        }
    };

    for ($x = 0; $x < $w; $x++) {
        $seed($x, 0);
        $seed($x, $h - 1);
    }
    for ($y = 0; $y < $h; $y++) {
        $seed(0, $y);
        $seed($w - 1, $y);
    }

    while ($stack) {
        $i = array_pop($stack);
        $x = $i % $w;
        $y = intdiv($i, $w);
        if ($x > 0) {
            $seed($x - 1, $y);
        }
        if ($x < $w - 1) {
            $seed($x + 1, $y);
        }
        if ($y > 0) {
            $seed($x, $y - 1);
        }
        if ($y < $h - 1) {
            $seed($x, $y + 1);
        }
    }

    // Enclosed pockets the border flood cannot reach (between an arm and the body, say).
    // A pocket of checkerboard has plenty of light-grey squares (tones 225-243); the white of an
    // eye or of a sign is almost only bright white.
    $seenPocket = $gone;
    for ($start = 0; $start < $w * $h; $start++) {
        if ($seenPocket[$start] !== "\0" || ! isBackgroundPixel(imagecolorat($image, $start % $w, intdiv($start, $w)))) {
            continue;
        }

        $pocket = [$start];
        $seenPocket[$start] = "\1";
        $grey = 0;
        for ($k = 0; $k < count($pocket); $k++) {
            $x = $pocket[$k] % $w;
            $y = intdiv($pocket[$k], $w);
            $tone = imagecolorat($image, $x, $y) & 255;
            if ($tone >= 225 && $tone <= 243) {
                $grey++;
            }
            foreach ([[$x - 1, $y], [$x + 1, $y], [$x, $y - 1], [$x, $y + 1]] as [$nx, $ny]) {
                $ni = $ny * $w + $nx;
                if ($nx >= 0 && $nx < $w && $ny >= 0 && $ny < $h && $seenPocket[$ni] === "\0" && isBackgroundPixel(imagecolorat($image, $nx, $ny))) {
                    $seenPocket[$ni] = "\1";
                    $pocket[] = $ni;
                }
            }
        }

        // Measured: checker pockets have 28-48 % light-grey squares, eye whites 8-12 %.
        if (count($pocket) >= 20 && $grey / count($pocket) >= 0.20) {
            foreach ($pocket as $i) {
                $gone[$i] = "\1";
            }
        }
    }

    // Light halo: edge pixels that are still mostly checkerboard (bright, barely coloured).
    for ($pass = 0; $pass < 2; $pass++) {
        $halo = [];
        for ($i = 0, $n = $w * $h; $i < $n; $i++) {
            if ($gone[$i] !== "\0") {
                continue;
            }
            $x = $i % $w;
            $y = intdiv($i, $w);
            $touches = false;
            foreach ([[-1, 0], [1, 0], [0, -1], [0, 1], [-1, -1], [1, -1], [-1, 1], [1, 1]] as [$dx, $dy]) {
                $nx = $x + $dx;
                $ny = $y + $dy;
                if ($nx >= 0 && $nx < $w && $ny >= 0 && $ny < $h && $gone[$ny * $w + $nx] === "\1") {
                    $touches = true;
                    break;
                }
            }
            if (! $touches) {
                continue;
            }
            $rgba = imagecolorat($image, $x, $y);
            $r = ($rgba >> 16) & 255;
            $g = ($rgba >> 8) & 255;
            $b = $rgba & 255;
            if (min($r, $g, $b) >= 205 && (max($r, $g, $b) - min($r, $g, $b)) <= 14) {
                $halo[] = $i;
            }
        }
        foreach ($halo as $i) {
            $gone[$i] = "\1";
        }
    }

    // Light-blue speed lines and glows that sit on the checkerboard carry its pattern. Fur, cream
    // and white signs are warm (blue below red), the bandana is saturated, so light and cool
    // pixels are peeled off from the outside in, one layer at a time.
    $isCool = function (int $rgba): bool {
        $r = ($rgba >> 16) & 255;
        $g = ($rgba >> 8) & 255;
        $b = $rgba & 255;

        return min($r, $g, $b) >= 150 && ($b - $r) >= 12;
    };
    $queue = [];
    for ($i = 0, $n = $w * $h; $i < $n; $i++) {
        if ($gone[$i] !== "\1") {
            continue;
        }
        $x = $i % $w;
        $y = intdiv($i, $w);
        foreach ([[$x - 1, $y], [$x + 1, $y], [$x, $y - 1], [$x, $y + 1]] as [$nx, $ny]) {
            $ni = $ny * $w + $nx;
            if ($nx >= 0 && $nx < $w && $ny >= 0 && $ny < $h && $gone[$ni] === "\0" && $isCool(imagecolorat($image, $nx, $ny))) {
                $gone[$ni] = "\1";
                $queue[] = $ni;
            }
        }
    }
    while ($queue) {
        $i = array_pop($queue);
        $x = $i % $w;
        $y = intdiv($i, $w);
        foreach ([[$x - 1, $y], [$x + 1, $y], [$x, $y - 1], [$x, $y + 1]] as [$nx, $ny]) {
            $ni = $ny * $w + $nx;
            if ($nx >= 0 && $nx < $w && $ny >= 0 && $ny < $h && $gone[$ni] === "\0" && $isCool(imagecolorat($image, $nx, $ny))) {
                $gone[$ni] = "\1";
                $queue[] = $ni;
            }
        }
    }

    // Loose specks that are not part of the figure.
    if ($minArea > 0) {
        $seen = $gone;   // the background already counts as seen
        for ($start = 0; $start < $w * $h; $start++) {
            if ($seen[$start] !== "\0") {
                continue;
            }

            $island = [$start];
            $seen[$start] = "\1";
            for ($k = 0; $k < count($island); $k++) {
                $x = $island[$k] % $w;
                $y = intdiv($island[$k], $w);
                foreach ([[$x - 1, $y], [$x + 1, $y], [$x, $y - 1], [$x, $y + 1]] as [$nx, $ny]) {
                    if ($nx >= 0 && $nx < $w && $ny >= 0 && $ny < $h && $seen[$ny * $w + $nx] === "\0") {
                        $seen[$ny * $w + $nx] = "\1";
                        $island[] = $ny * $w + $nx;
                    }
                }
            }

            if (count($island) < $minArea) {
                foreach ($island as $i) {
                    $gone[$i] = "\1";
                }
            }
        }
    }

    $removed = 0;
    for ($i = 0, $n = $w * $h; $i < $n; $i++) {
        if ($gone[$i] === "\1") {
            imagesetpixel($image, $i % $w, intdiv($i, $w), $clear);
            $removed++;
        }
    }

    return $removed;
}

// 0) A fake checkerboard "transparency" is opaque in all four corners: remove it first.
$opaqueCorners = 0;
foreach ([[0, 0], [$width - 1, 0], [0, $height - 1], [$width - 1, $height - 1]] as [$cornerX, $cornerY]) {
    $opaqueCorners += (((imagecolorat($image, $cornerX, $cornerY) >> 24) & 127) === 0) ? 1 : 0;
}
if ($opaqueCorners === 4) {
    $removedPixels = removeBackground($image, (int) ($options['minarea'] ?? 0));
    printf("baked-in background removed (%.0f%% of the pixels)\n", 100 * $removedPixels / ($width * $height));
}

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
