<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Services\IconGenerator;
use Illuminate\Http\Response;

class ChildIconController extends Controller
{
    public function show(Child $child, int $size, IconGenerator $generator): Response
    {
        $size = min(max($size, 32), 512);

        $png = $generator->childIcon($child->name, $child->color_theme, $size);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
