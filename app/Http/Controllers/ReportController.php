<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Payment;
use App\Models\ScooterTrip;
use App\Models\FuelEntry;
use App\Models\Mistake;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filter = $request->get('preset', 'this_month');

        [$startDate, $endDate] = $this->resolveDateRange($filter, $request->from_date, $request->to_date);

        $expenses = Expense::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('category')
            ->orderBy('date')
            ->get();

        $incomes = Income::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('category')
            ->orderBy('date')
            ->get();

        $payments = Payment::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        $trips = ScooterTrip::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        $fuelEntries = FuelEntry::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        $mistakes = Mistake::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('category')
            ->orderBy('date')
            ->get();

        $totalExpenses = $expenses->sum('amount');
        $totalIncome = $incomes->sum('amount');
        $totalDistance = $trips->sum('distance_km');
        $totalPetrol = $fuelEntries->sum('amount');

        return view('reports.index', compact(
            'filter',
            'startDate',
            'endDate',
            'expenses',
            'incomes',
            'payments',
            'trips',
            'fuelEntries',
            'mistakes',
            'totalExpenses',
            'totalIncome',
            'totalDistance',
            'totalPetrol'
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        $module = $request->get('module', 'expenses');
        $filter = $request->get('preset', 'this_month');
        [$startDate, $endDate] = $this->resolveDateRange($filter, $request->from_date, $request->to_date);

        $filename = "{$module}_report_{$startDate}_to_{$endDate}.csv";

        $response = new StreamedResponse(function () use ($user, $module, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');

            if ($module === 'expenses') {
                fputcsv($handle, ['ID', 'Date', 'Time', 'Category', 'Description', 'Amount', 'Payment Method', 'Paid By', 'Notes']);
                $records = Expense::where('user_id', $user->id)->whereBetween('date', [$startDate, $endDate])->with('category')->get();
                foreach ($records as $r) {
                    fputcsv($handle, [$r->id, $r->date->toDateString(), $r->time, $r->category?->name ?? 'Other', $r->description, $r->amount, $r->payment_method, $r->paid_by, $r->notes]);
                }
            } elseif ($module === 'income') {
                fputcsv($handle, ['ID', 'Date', 'Source', 'Description', 'Amount', 'Payment Method', 'Notes']);
                $records = Income::where('user_id', $user->id)->whereBetween('date', [$startDate, $endDate])->get();
                foreach ($records as $r) {
                    fputcsv($handle, [$r->id, $r->date->toDateString(), $r->source, $r->description, $r->amount, $r->payment_method, $r->notes]);
                }
            } elseif ($module === 'petrol') {
                fputcsv($handle, ['ID', 'Date', 'Amount', 'Litres', 'Price/Litre', 'Odometer', 'Station', 'Payment Method']);
                $records = FuelEntry::where('user_id', $user->id)->whereBetween('date', [$startDate, $endDate])->get();
                foreach ($records as $r) {
                    fputcsv($handle, [$r->id, $r->date->toDateString(), $r->amount, $r->litres, $r->price_per_litre, $r->odometer, $r->petrol_station, $r->payment_method]);
                }
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");

        return $response;
    }

    private function resolveDateRange(string $preset, ?string $customFrom = null, ?string $customTo = null): array
    {
        $today = Carbon::today();

        return match ($preset) {
            'today' => [$today->toDateString(), $today->toDateString()],
            'yesterday' => [Carbon::yesterday()->toDateString(), Carbon::yesterday()->toDateString()],
            'this_week' => [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()],
            'last_week' => [$today->copy()->subWeek()->startOfWeek()->toDateString(), $today->copy()->subWeek()->endOfWeek()->toDateString()],
            'this_month' => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
            'last_month' => [$today->copy()->subMonth()->startOfMonth()->toDateString(), $today->copy()->subMonth()->endOfMonth()->toDateString()],
            'custom' => [$customFrom ?? $today->toDateString(), $customTo ?? $today->toDateString()],
            default => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
        };
    }
}
