<?php

namespace App\Http\Controllers;

use App\Models\FamilyMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FamilyMemberController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = FamilyMember::where('user_id', $user->id);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('relationship', 'like', "%{$search}%")
                    ->orWhere('details', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $members = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('family_members.index', compact('members'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'relationship' => 'nullable|string|max:100',
            'details' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
        ]);

        FamilyMember::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'relationship' => $validated['relationship'] ?? null,
            'details' => $validated['details'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        return redirect()->route('family-members.index')->with('success', 'Family member added successfully!');
    }

    public function update(Request $request, FamilyMember $familyMember): RedirectResponse
    {
        abort_if($familyMember->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'relationship' => 'nullable|string|max:100',
            'details' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
        ]);

        $familyMember->update($validated);

        return redirect()->route('family-members.index')->with('success', 'Family member updated.');
    }

    public function destroy(Request $request, FamilyMember $familyMember): RedirectResponse
    {
        abort_if($familyMember->user_id !== $request->user()->id, 403);

        $familyMember->delete();

        return redirect()->route('family-members.index')->with('success', 'Family member removed.');
    }
}
