<?php

namespace App\Services;

use App\Models\GradeItem;
use App\Models\GradeTransaction;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchService
{
    // Products = Subjects: hanapin ayon sa name/SKU, category, supplier, status
    public function products(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return GradeItem::with('supplier')
            ->filter($filters)
            ->orderBy('subject_code')
            ->paginate($perPage)
            ->withQueryString();
    }

    // Purchase Orders: hanapin ayon sa reference, supplier, status, date range
    public function purchaseOrders(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return PurchaseOrder::with('supplier')
            ->filter($filters)
            ->latest()
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    // Transactions: hanapin ayon sa product, type, user, date range
    public function transactions(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return GradeTransaction::with(['gradeItem', 'user'])
            ->filter($filters)
            ->recent()
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}