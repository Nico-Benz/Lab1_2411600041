<?php

namespace App\Http\Controllers;

use App\Http\Resources\GradeItemResource;
use App\Http\Resources\GradeTransactionResource;
use App\Models\GradeItem;
use App\Models\GradeTransaction;
use App\Support\DashboardStats;
use App\Support\ProcurementWidgets;
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

    public function pendingOrders(): JsonResponse
    {
        return response()->json([
            'count' => ProcurementWidgets::pendingCount(),
        ]);
    }

    public function recentOrders(): JsonResponse
    {
        $orders = ProcurementWidgets::recentOrders()->map(fn ($po) => [
            'id' => $po->id,
            'reference_number' => $po->reference_number,
            'supplier' => $po->supplier?->company_name ?? '—',
            'status' => $po->status,
            'total' => (float) $po->total,
            'url' => route('purchase-orders.show', $po),
        ]);

        return response()->json(['orders' => $orders]);
    }

    public function supplierPerformance(): JsonResponse
    {
        $suppliers = ProcurementWidgets::topSuppliers()->map(fn ($row) => [
            'id' => $row['id'],
            'name' => $row['name'],
            'rating' => $row['rating'],
            'url' => route('suppliers.show', $row['id']),
        ]);

        return response()->json(['suppliers' => $suppliers]);
    }

    public function reorderAlerts(): JsonResponse
    {
        $alerts = ProcurementWidgets::reorderAlerts();

        return response()->json([
            'count' => $alerts->count(),
            'alerts' => $alerts,
        ]);
    }
}