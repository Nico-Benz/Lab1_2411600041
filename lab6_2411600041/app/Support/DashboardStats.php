<?php

namespace App\Support;

use App\Models\GradeItem;

class DashboardStats
{
    public static function summary(): array
    {
        $allItems = GradeItem::all();
        $gradedItems = $allItems->where('current_grade', '>', 0);

        $totalUnits = $allItems->sum('units');
        $completedUnits = $gradedItems->sum('units');

        return [
            'totalSubjects' => $allItems->count(),
            'averageGrade' => $gradedItems->avg('current_grade') ?? 0,
            'passedCount' => $gradedItems->filter(fn ($item) => ! $item->isBelowThreshold())->count(),
            'pendingCount' => $allItems->filter(fn ($item) => $item->isPending())->count(),
            'belowThresholdCount' => $allItems->filter(fn ($item) => $item->isBelowThreshold())->count(),
            'completionRate' => $totalUnits > 0 ? round(($completedUnits / $totalUnits) * 100, 1) : 0,
            'categoryDistribution' => $allItems->groupBy('category')->map->count(),
        ];
    }
}