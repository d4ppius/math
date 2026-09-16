<?php

namespace App\Services;

/**
 * Renders small PNG icons with plain GD (no image assets to ship).
 * Used for the PWA app icons and each child's personalized
 * apple-touch-icon (a colored circle with their first initial).
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

    /**
     * The main app icon: an orange rounded square with a white "×".
     * Content is kept within a safe zone so it also works as a
     * maskable icon.
     */
    public function appIcon(int $size): string
    {
        $image = imagecreatetruecolor($size, $size);
        imagesavealpha($image, true);
        imagealphablending($image, true);

        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);

        $bg = $this->allocateHex($image, '#f97316');
        $this->filledRoundedSquare($image, $size, $bg, (int) round($size * 0.18));

        $white = imagecolorallocate($image, 255, 255, 255);
        $fontSize = (int) round($size * 0.42);
        $this->centeredText($image, '×', $fontSize, $white, $size);

        return $this->toPngString($image);
    }

    public function childIcon(string $name, string $colorTheme, int $size): string
    {
        $image = imagecreatetruecolor($size, $size);
        imagesavealpha($image, true);
        imagealphablending($image, true);

        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);

        $hex = self::COLOR_HEXES[$colorTheme] ?? self::COLOR_HEXES['orange'];
        $bg = $this->allocateHex($image, $hex);

        $center = (int) ($size / 2);
        $radius = (int) round($size * 0.47);
        imagefilledellipse($image, $center, $center, $radius * 2, $radius * 2, $bg);

        $white = imagecolorallocate($image, 255, 255, 255);
        $initial = mb_strtoupper(mb_substr($name, 0, 1));
        $fontSize = (int) round($size * 0.4);
        $this->centeredText($image, $initial, $fontSize, $white, $size);

        return $this->toPngString($image);
    }

    private function allocateHex($image, string $hex): int
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return imagecolorallocate($image, $r, $g, $b);
    }

    private function filledRoundedSquare($image, int $size, int $color, int $radius): void
    {
        imagefilledrectangle($image, $radius, 0, $size - $radius, $size, $color);
        imagefilledrectangle($image, 0, $radius, $size, $size - $radius, $color);
        imagefilledellipse($image, $radius, $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $size - $radius, $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $radius, $size - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $size - $radius, $size - $radius, $radius * 2, $radius * 2, $color);
    }

    private function centeredText($image, string $text, int $fontSize, int $color, int $canvasSize): void
    {
        $font = 5; // GD built-in font, scaled via imagestring is fixed size; use TTF fallback below if available.
        $ttf = $this->builtinFontPath();

        if ($ttf) {
            $box = imagettfbbox($fontSize, 0, $ttf, $text);
            $textWidth = $box[2] - $box[0];
            $textHeight = $box[1] - $box[7];
            $x = (int) (($canvasSize - $textWidth) / 2 - $box[0]);
            $y = (int) (($canvasSize - $textHeight) / 2 - $box[7]);
            imagettftext($image, $fontSize, 0, $x, $y, $color, $ttf, $text);

            return;
        }

        // Fallback: GD's built-in bitmap font, centered as best effort.
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        imagestring($image, $font, (int) (($canvasSize - $textWidth) / 2), (int) (($canvasSize - $textHeight) / 2), $text, $color);
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
