<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Expense;
use App\Models\FoodEntry;
use App\Models\FriendTransaction;
use App\Models\FuelEntry;
use App\Models\Income;
use App\Models\Mistake;
use App\Models\Note;
use App\Models\Payment;
use App\Models\ScooterTrip;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $user = $request->user();
        $q = trim($request->get('q', ''));

        if (empty($q)) {
            return view('search.index', ['query' => '', 'results' => []]);
        }

        $results = [
            'expenses' => Expense::where('user_id', $user->id)
                ->where(fn ($query) => $query->where('description', 'like', "%{$q}%")->orWhere('notes', 'like', "%{$q}%")->orWhere('friend_person', 'like', "%{$q}%"))
                ->take(10)->get(),

            'income' => Income::where('user_id', $user->id)
                ->where(fn ($query) => $query->where('source', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%")->orWhere('notes', 'like', "%{$q}%"))
                ->take(10)->get(),

            'payments' => Payment::where('user_id', $user->id)
                ->where(fn ($query) => $query->where('paid_to', 'like', "%{$q}%")->orWhere('purpose', 'like', "%{$q}%")->orWhere('notes', 'like', "%{$q}%"))
                ->take(10)->get(),

            'friend_transactions' => FriendTransaction::where('user_id', $user->id)
                ->whereHas('friend', fn ($friendQuery) => $friendQuery->where('name', 'like', "%{$q}%"))
                ->orWhere('description', 'like', "%{$q}%")
                ->with('friend')
                ->take(10)->get(),

            'activities' => Activity::where('user_id', $user->id)
                ->where(fn ($query) => $query->where('title', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%")->orWhere('location', 'like', "%{$q}%"))
                ->take(10)->get(),

            'food' => FoodEntry::where('user_id', $user->id)
                ->where(fn ($query) => $query->where('item_name', 'like', "%{$q}%")->orWhere('location', 'like', "%{$q}%")->orWhere('notes', 'like', "%{$q}%"))
                ->take(10)->get(),

            'scooter' => ScooterTrip::where('user_id', $user->id)
                ->where(fn ($query) => $query->where('title', 'like', "%{$q}%")->orWhere('start_address', 'like', "%{$q}%")->orWhere('end_address', 'like', "%{$q}%")->orWhere('notes', 'like', "%{$q}%"))
                ->take(10)->get(),

            'petrol' => FuelEntry::where('user_id', $user->id)
                ->where(fn ($query) => $query->where('petrol_station', 'like', "%{$q}%")->orWhere('notes', 'like', "%{$q}%"))
                ->take(10)->get(),

            'mistakes' => Mistake::where('user_id', $user->id)
                ->where(fn ($query) => $query->where('title', 'like', "%{$q}%")->orWhere('what_happened', 'like', "%{$q}%")->orWhere('lesson_learned', 'like', "%{$q}%")->orWhere('tags', 'like', "%{$q}%"))
                ->take(10)->get(),

            'notes' => Note::where('user_id', $user->id)
                ->where(fn ($query) => $query->where('title', 'like', "%{$q}%")->orWhere('content', 'like', "%{$q}%")->orWhere('tags', 'like', "%{$q}%"))
                ->take(10)->get(),
        ];

        $totalMatches = array_sum(array_map(fn ($arr) => count($arr), $results));

        return view('search.index', compact('q', 'results', 'totalMatches'));
    }
}
