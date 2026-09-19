<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Child;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChildController extends Controller
{
    public function index(Request $request): View
    {
        $term = $request->query('q');

        $children = Child::query()
            ->with('family')
            ->when($term, fn ($query) => $query
                ->where('name', 'like', '%'.$term.'%')
                ->orWhereHas('family', fn ($family) => $family->where('name', 'like', '%'.$term.'%')))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.children.index', ['children' => $children]);
    }
}
