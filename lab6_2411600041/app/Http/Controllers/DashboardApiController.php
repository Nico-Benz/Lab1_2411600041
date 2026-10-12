<?php

namespace App\Http\Controllers;

use App\Http\Resources\GradeItemResource;
use App\Http\Resources\GradeTransactionResource;
use App\Models\GradeItem;
use App\Models\GradeTransaction;
use App\Support\DashboardStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $s = DashboardStats::summary();

        return response()->json([
            'stats' => [
                'total_subjects' => $s['totalSubjects'],
                'average_grade' => round((float) $s['averageGrade'], 2),
                'completion_rate' => (float) $s['completionRate'],
                'passed_count' => $s['passedCount'],
                'pending_count' => $s['pendingCount'],
                'at_risk_count' => $s['belowThresholdCount'],
            ],
            'category_distribution' => $s['categoryDistribution'],
            'unread_notifications' => $request->user()->unreadNotifications()->count(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function atRisk()
    {
        return GradeItemResource::collection(
            GradeItem::atRisk()->orderBy('current_grade')->get()
        );
    }

    public function recentTransactions()
    {
        $transactions = GradeTransaction::with(['gradeItem', 'user'])
            ->recent()
            ->orderByDesc('id')
            ->take(10)
            ->get();

        return GradeTransactionResource::collection($transactions);
    }
}