<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $movements = $this->movementQuery($from, $to)->get();
        $lowStock = Product::with(['category', 'supplier'])->lowStock()->orderBy('quantity')->get();
        $products = Product::with('category')->orderBy('name')->get();
        $totalValue = $products->sum(fn ($p) => $p->quantity * $p->unit_price);

        return view('reports.index', compact('from', 'to', 'movements', 'lowStock', 'products', 'totalValue'));
    }

    public function export(Request $request, string $type)
    {
        abort_unless(in_array($type, ['movements', 'low-stock', 'valuation']), 404);

        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        return response()->streamDownload(function () use ($type, $from, $to) {
            $out = fopen('php://output', 'w');

            if ($type === 'movements') {
                fputcsv($out, ['Date', 'SKU', 'Product', 'Type', 'Change', 'Balance After', 'Reference', 'User']);
                foreach ($this->movementQuery($from, $to)->get() as $t) {
                    fputcsv($out, [
                        $t->created_at->format('Y-m-d H:i'), $t->product->sku ?? '', $t->product->name ?? '',
                        $t->type_label, $t->signed_change, $t->quantity_after, $t->reference_number, $t->user->name ?? '',
                    ]);
                }
            } elseif ($type === 'low-stock') {
                fputcsv($out, ['SKU', 'Product', 'Category', 'Quantity', 'Reorder Level', 'Supplier', 'Status']);
                foreach (Product::with(['category', 'supplier'])->lowStock()->orderBy('quantity')->get() as $p) {
                    fputcsv($out, [$p->sku, $p->name, $p->category->name ?? '', $p->quantity, $p->reorder_level, $p->supplier->name ?? '', $p->stock_status]);
                }
            } else {
                fputcsv($out, ['SKU', 'Product', 'Quantity', 'Unit Price', 'Total Value']);
                foreach (Product::orderBy('name')->get() as $p) {
                    fputcsv($out, [$p->sku, $p->name, $p->quantity, $p->unit_price, round($p->quantity * $p->unit_price, 2)]);
                }
            }

            fclose($out);
        }, "report-{$type}-" . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function movementQuery(string $from, string $to)
    {
        return InventoryTransaction::with(['product', 'user'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->recent();
    }
}
