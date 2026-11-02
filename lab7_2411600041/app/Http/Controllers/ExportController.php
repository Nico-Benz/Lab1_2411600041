<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Exports\PurchaseOrdersExport;
use App\Exports\SupplierPerformanceExport;
use App\Exports\TransactionsExport;
use App\Services\ExportService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    // type => [export class, filters na tatanggapin, pangalan ng file]
    private const EXPORTS = [
        'subjects' => [
            ProductsExport::class,
            ['search', 'category', 'supplier_id', 'status'],
            'subjects',
        ],
        'purchase-orders' => [
            PurchaseOrdersExport::class,
            ['search', 'supplier_id', 'status', 'date_from', 'date_to'],
            'purchase-orders',
        ],
        'transactions' => [
            TransactionsExport::class,
            ['grade_item_id', 'type', 'user_id', 'date_from', 'date_to'],
            'transactions',
        ],
        'supplier-performance' => [
            SupplierPerformanceExport::class,
            [],
            'supplier-performance',
        ],
    ];

    public function download(Request $request, ExportService $exports, string $report, string $format)
    {
        // Kapag hindi kilala ang type o hindi csv ang format: 404
        abort_unless(isset(self::EXPORTS[$report]) && in_array($format, ['csv', 'pdf'], true), 404);

        [$class, $filterKeys, $name] = self::EXPORTS[$report];

        $export = new $class($request->only($filterKeys));
        $filename = $name . '-' . now()->format('Ymd-His') . '.' . $format;

        return $format === 'pdf'
            ? $exports->pdf($export, $filename)
            : $exports->csv($export, $filename);
    }
}