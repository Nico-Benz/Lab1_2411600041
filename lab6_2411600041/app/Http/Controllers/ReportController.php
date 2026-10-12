<?php

namespace App\Http\Controllers;

use App\Models\GradeItem;
use App\Models\GradeTransaction;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $from = $request->input('date_from', now()->subDays(30)->toDateString());
        $to = $request->input('date_to', now()->toDateString());

        $movements = GradeTransaction::with(['gradeItem', 'user'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $movementSummary = [
            'total' => $movements->count(),
            'increase' => $movements->where('type', 'increase')->count(),
            'decrease' => $movements->where('type', 'decrease')->count(),
            'adjustment' => $movements->where('type', 'adjustment')->count(),
        ];

        $atRisk = GradeItem::atRisk()->orderBy('current_grade')->get();

        $graded = GradeItem::where('current_grade', '>', 0)->get();
        $gradedUnits = $graded->sum('units');
        $weightedAverage = $gradedUnits > 0
            ? $graded->sum(fn ($item) => (float) $item->current_grade * $item->units) / $gradedUnits
            : 0;

        $summary = [
            'weightedAverage' => $weightedAverage,
            'gradedSubjects' => $graded->count(),
            'pendingSubjects' => GradeItem::pending()->count(),
            'gradedUnits' => $gradedUnits,
            'highest' => $graded->sortByDesc('current_grade')->first(),
            'lowest' => $graded->sortBy('current_grade')->first(),
        ];

        return view('reports.index', compact(
            'from', 'to', 'movements', 'movementSummary', 'atRisk', 'summary'
        ));
    }
}