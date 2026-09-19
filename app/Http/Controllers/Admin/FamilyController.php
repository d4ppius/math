<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Family;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FamilyController extends Controller
{
    public function index(Request $request): View
    {
        $families = Family::query()
            ->withCount(['users', 'children'])
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->query('q').'%'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.families.index', ['families' => $families]);
    }

    public function show(Family $family): View
    {
        $family->load(['users', 'children']);

        return view('admin.families.show', ['family' => $family]);
    }

    public function update(Request $request, Family $family): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'timezone:all'],
        ]);

        $family->update($validated);

        return redirect()->route('admin.families.show', $family)->with('status', 'Familie gespeichert.');
    }

    public function destroy(Request $request, Family $family): RedirectResponse
    {
        if ($family->id === $request->user()->family_id) {
            return back()->with('error', 'Die eigene Familie kann nicht gelöscht werden.');
        }

        // Users, children and everything hanging off them are removed by
        // the database's cascading foreign keys.
        $family->delete();

        return redirect()->route('admin.families.index')->with('status', 'Familie wurde gelöscht.');
    }
}
