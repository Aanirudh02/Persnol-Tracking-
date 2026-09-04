<?php

namespace App\Http\Controllers;

use App\Models\LookupOption;
use App\Services\OptionsService;
use Illuminate\Http\Request;

class OptionController extends Controller
{
    public function store(Request $request, OptionsService $options)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:50',
            'name' => 'required|string|max:100',
        ]);

        $option = $options->create($validated['type'], $validated['name'], $request->user()->id);

        if ($request->wantsJson()) {
            return response()->json($option);
        }

        return back()->with('success', 'Option added.');
    }

    public function destroy(Request $request, LookupOption $option)
    {
        if ($option->is_system || ($option->user_id && $option->user_id !== $request->user()->id)) {
            abort(403);
        }

        $type = $option->type;
        $userId = $option->user_id;
        $option->delete();
        LookupOption::clearCache($userId, $type);

        return back()->with('success', 'Option removed.');
    }
}
