<?php

namespace App\Http\Controllers;

use App\Models\GradeItem;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $allItems = GradeItem::all();
        $gradedItems = $allItems->where('current_grade', '>', 0);

        $totalSubjects = $allItems->count();
        $averageGrade = $gradedItems->avg('current_grade') ?? 0;
        $passedCount = $gradedItems->filter(fn ($item) => !$item->isBelowThreshold())->count();
        $pendingCount = $allItems->filter(fn ($item) => $item->isPending())->count();
        $belowThresholdCount = $allItems->filter(fn ($item) => $item->isBelowThreshold())->count();

        $totalUnits = $allItems->sum('units');
        $completedUnits = $gradedItems->sum('units');
        $completionRate = $totalUnits > 0 ? round(($completedUnits / $totalUnits) * 100, 1) : 0;

        $categoryDistribution = $allItems->groupBy('category')->map->count();

        $recentItems = GradeItem::orderBy('updated_at', 'desc')->take(6)->get();

        $hour = now()->format('H');
        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');

        return view('dashboard', compact(
            'greeting',
            'totalSubjects',
            'averageGrade',
            'passedCount',
            'pendingCount',
            'belowThresholdCount',
            'completionRate',
            'categoryDistribution',
            'recentItems'
        ));
    }
}