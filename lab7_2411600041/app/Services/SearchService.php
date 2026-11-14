<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * One place for the complex search / filter queries.
 * Every method returns a query Builder, so the caller can paginate, get() or export it.
 */
class SearchService
{
    /** q, category_id, brand_id, supplier_id, stock (in|low|out) */
    public function products(Request $request): Builder
    {
        return Product::query()
            ->with(['brand', 'category', 'supplier'])
            ->search($request->input('q'))
            ->when($request->filled('category_id'), fn (Builder $q) => $q->inCategory($request->input('category_id')))
            ->when($request->filled('brand_id'), fn (Builder $q) => $q->where('brand_id', $request->input('brand_id')))
            ->fromSupplier($request->input('supplier_id'))
            ->stockStatus($request->input('stock'))
            ->orderBy('name');
    }

    /** q (reference or supplier), status, supplier_id, from, to */
    public function purchaseOrders(Request $request): Builder
    {
        return PurchaseOrder::query()
            ->search($request->input('q'))
            ->byStatus($request->input('status'))
            ->forSupplier($request->input('supplier_id'))
            ->dateBetween($request->input('from'), $request->input('to'));
    }

    /** q, product_id, type, user_id, from, to */
    public function transactions(Request $request): Builder
    {
        return InventoryTransaction::query()
            ->with(['product', 'user'])
            ->search($request->input('q'))
            ->forProduct($request->input('product_id'))
            ->ofType($request->input('type'))
            ->byUser($request->input('user_id'))
            ->dateBetween($request->input('from'), $request->input('to'));
    }

    /** q, status (active|inactive|deleted), sort, direction */
    public function suppliers(Request $request): Builder
    {
        $query = Supplier::query()->search($request->input('q'));

        match ($request->input('status')) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            'deleted' => $request->user()?->isAdmin() ? $query->onlyTrashed() : $query,
            default => null,
        };

        $sort = in_array($request->input('sort'), ['name', 'contact_person', 'lead_time_days', 'payment_terms', 'created_at'], true)
            ? $request->input('sort')
            : 'name';
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $direction);
    }
}
