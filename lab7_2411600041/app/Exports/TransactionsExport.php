<?php

namespace App\Exports;

use App\Services\SearchService;
use Illuminate\Http\Request;

class TransactionsExport implements ReportExport
{
    public function __construct(private Request $request)
    {
    }

    public function title(): string
    {
        return 'Inventory Transactions';
    }

    public function filename(): string
    {
        return 'transactions-' . now()->format('Ymd-His');
    }

    public function headings(): array
    {
        return ['Date', 'SKU', 'Product', 'Type', 'Change', 'Before', 'After', 'Reference', 'By'];
    }

    public function rows(): iterable
    {
        $transactions = (new SearchService)->transactions($this->request)->recent()->get();

        foreach ($transactions as $transaction) {
            yield [
                $transaction->created_at->format('Y-m-d H:i'),
                $transaction->product->sku ?? '',
                $transaction->product->name ?? '',
                $transaction->type_label,
                $transaction->signed_change,
                $transaction->quantity_before,
                $transaction->quantity_after,
                $transaction->reference_number ?? '',
                $transaction->user->name ?? '',
            ];
        }
    }
}
