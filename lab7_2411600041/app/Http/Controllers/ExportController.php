<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Exports\PurchaseOrdersExport;
use App\Exports\ReportExport;
use App\Exports\SupplierPerformanceExport;
use App\Exports\TransactionsExport;
use App\Services\PdfService;
use Illuminate\Http\Request;

/** GET /exports/{report}/{format}  -> report: products, purchase-orders, transactions, supplier-performance | format: csv, pdf */
class ExportController extends Controller
{
    public function download(Request $request, string $report, string $format)
    {
        abort_unless(in_array($format, ['csv', 'pdf'], true), 404);

        $export = match ($report) {
            'products' => new ProductsExport($request),
            'purchase-orders' => new PurchaseOrdersExport($request),
            'transactions' => new TransactionsExport($request),
            'supplier-performance' => new SupplierPerformanceExport(),
            default => abort(404),
        };

        return $format === 'csv' ? $this->csv($export) : $this->pdf($export);
    }

    private function csv(ReportExport $export)
    {
        return response()->streamDownload(function () use ($export) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads the file as UTF-8
            fputcsv($out, $export->headings(), ',', '"', '');

            foreach ($export->rows() as $row) {
                fputcsv($out, $row, ',', '"', '');
            }

            fclose($out);
        }, $export->filename() . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function pdf(ReportExport $export)
    {
        if (! PdfService::available()) {
            return back()->with('error', 'PDF export needs the dompdf package. Run this in the terminal: composer require barryvdh/laravel-dompdf');
        }

        return PdfService::download('exports.table', [
            'title' => $export->title(),
            'headings' => $export->headings(),
            'rows' => iterator_to_array($export->rows(), false),
            'generatedAt' => now()->format('M d, Y g:i A'),
        ], $export->filename() . '.pdf', 'landscape');
    }
}
