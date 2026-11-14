<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseOrderResource;
use App\View\Composers\ProcurementWidgets;

/** JSON endpoints for the procurement widgets (AJAX refresh). They use the same code as the View Composers. */
class ProcurementApiController extends Controller
{
    public function __construct(private ProcurementWidgets $widgets)
    {
    }

    /** GET /api/dashboard/pending-orders */
    public function pendingOrders()
    {
        $data = $this->widgets->pendingOrders();

        return response()->json([
            'count' => $data['pendingCount'],
            'value' => $data['pendingValue'],
            'orders' => PurchaseOrderResource::collection($data['pendingOrders'])->resolve(),
        ]);
    }

    /** GET /api/dashboard/recent-orders */
    public function recentOrders()
    {
        $data = $this->widgets->recentOrders();

        return response()->json([
            'orders' => PurchaseOrderResource::collection($data['recentOrders'])->resolve(),
        ]);
    }

    /** GET /api/dashboard/supplier-performance */
    public function supplierPerformance()
    {
        return response()->json(['suppliers' => $this->widgets->supplierPerformance()['topSuppliers']]);
    }

    /** GET /api/dashboard/reorder-alerts */
    public function reorderAlerts()
    {
        return response()->json(['products' => $this->widgets->reorderAlerts()['reorderAlerts']]);
    }
}
