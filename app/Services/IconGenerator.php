<?php

namespace App\Services;

/**
 * Renders each child's personalized home-screen icon with plain GD: the
 * Rechenfuchs app icon plus a small badge in the child's colour showing
 * their first initial, so several children on one iPad stay tellable apart.
 * The artwork itself is the static public/images/icons/icon-base.png.
 */
class IconGenerator
{
    private const COLOR_HEXES = [
        'orange' => '#f97316',
        'blue' => '#3b82f6',
        'green' => '#22c55e',
        'pink' => '#ec4899',
        'purple' => '#a855f7',
    ];

    /** Bump whenever the icon artwork changes, so devices fetch it again. */
    public const VERSION = 2;

    /** GD has no anti-aliasing for ellipses, so draw large and scale down. */
    private const SUPERSAMPLE = 3;

    public function childIcon(string $name, string $colorTheme, int $size): string
    {
        $work = $size * self::SUPERSAMPLE;

        $base = imagecreatefrompng(public_path('images/icons/icon-base.png'));
        $canvas = imagecreatetruecolor($work, $work);
        imagecopyresampled($canvas, $base, 0, 0, 0, 0, $work, $work, imagesx($base), imagesy($base));

        $color = $this->allocateHex($canvas, self::COLOR_HEXES[$colorTheme] ?? self::COLOR_HEXES['orange']);
        $white = imagecolorallocate($canvas, 255, 255, 255);

        $center = (int) round($work * 0.74);
        $ring = (int) round($work * 0.38);
        $dot = (int) round($work * 0.31);
        imagefilledellipse($canvas, $center, $center, $ring, $ring, $white);
        imagefilledellipse($canvas, $center, $center, $dot, $dot, $color);

        $initial = mb_strtoupper(mb_substr($name, 0, 1));
        $this->centeredText($canvas, $initial, (int) round($work * 0.15), $white, $center, $center);

        $image = imagecreatetruecolor($size, $size);
        imagecopyresampled($image, $canvas, 0, 0, 0, 0, $size, $size, $work, $work);

        return $this->toPngString($image);
    }

    private function allocateHex($image, string $hex): int
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return imagecolorallocate($image, $r, $g, $b);
    }

    private function centeredText($image, string $text, int $fontSize, int $color, int $centerX, int $centerY): void
    {
        $ttf = $this->builtinFontPath();

        // imagettftext() only exists when GD was built with FreeType.
        if ($ttf && function_exists('imagettftext')) {
            $box = imagettfbbox($fontSize, 0, $ttf, $text);
            $textWidth = $box[2] - $box[0];
            $textHeight = $box[1] - $box[7];
            $x = (int) ($centerX - $textWidth / 2 - $box[0]);
            $y = (int) ($centerY - $textHeight / 2 - $box[7]);
            imagettftext($image, $fontSize, 0, $x, $y, $color, $ttf, $text);

            return;
        }

        // Fallback: GD's built-in bitmap font, centered as best effort.
        $font = 5;
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        imagestring($image, $font, (int) ($centerX - $textWidth / 2), (int) ($centerY - $textHeight / 2), $text, $color);
    }

    private function builtinFontPath(): ?string
    {
        $candidates = [
            '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
            '/System/Library/Fonts/Supplemental/Arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
        ];

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    private function toPngString($image): string
    {
        ob_start();
        imagepng($image);

        return ob_get_clean();
    }
}
