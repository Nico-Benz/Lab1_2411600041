@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>{{ $product->sku }} — {{ $product->name }}</h2>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">← Back to Products</a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card stat-card mb-4">
            <div class="card-body">
                <h5 class="card-title text-muted mb-3">Product Details</h5>

                <div class="row mb-2">
                    <div class="col-4 text-muted">SKU</div>
                    <div class="col-8">{{ $product->sku }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Product Name</div>
                    <div class="col-8">{{ $product->name }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Brand</div>
                    <div class="col-8">{{ $product->brand->name ?? '—' }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Description</div>
                    <div class="col-8">{{ $product->description ?? '—' }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Category</div>
                    <div class="col-8">{{ $product->category->name ?? '—' }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Unit Price</div>
                    <div class="col-8">₱{{ number_format($product->unit_price, 2) }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Reorder Level</div>
                    <div class="col-8">{{ $product->reorder_level }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Supplier</div>
                    <div class="col-8">{{ $product->supplier ?? '—' }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Stock Value</div>
                    <div class="col-8">₱{{ number_format($product->quantity * $product->unit_price, 2) }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Created</div>
                    <div class="col-8">{{ $product->created_at->format('M d, Y g:i A') }}</div>
                </div>
                <div class="row">
                    <div class="col-4 text-muted">Last Updated</div>
                    <div class="col-8">{{ $product->updated_at->format('M d, Y g:i A') }}</div>
                </div>
            </div>
        </div>

        <!-- Record stock movement (admin only) -->
        @can('admin')
        <div class="card stat-card mb-4">
            <div class="card-body">
                <h5 class="card-title text-muted mb-3">Record Stock Movement</h5>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('products.transactions.store', $product) }}" method="POST">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small">Type</label>
                            <select name="transaction_type" class="form-select" required>
                                <option value="in" {{ old('transaction_type') == 'in' ? 'selected' : '' }}>Stock In</option>
                                <option value="out" {{ old('transaction_type') == 'out' ? 'selected' : '' }}>Stock Out</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Quantity</label>
                            <input type="number" min="1" step="1" name="quantity" class="form-control" value="{{ old('quantity') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Reference No. (e.g. PO-1001)</label>
                            <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn w-100" style="background-color: var(--theme-primary); color: white;">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endcan

        <!-- Transaction history with running balance -->
        <div class="card stat-card">
            <div class="card-body">
                <h5 class="card-title text-muted mb-3">Transaction History</h5>

                <form method="GET" action="{{ route('products.show', $product) }}" class="row g-2 align-items-end mb-3">
                    <div class="col-md-4"><label class="form-label small">From</label><input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}"></div>
                    <div class="col-md-4"><label class="form-label small">To</label><input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}"></div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-dark">Filter</button>
                        <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Type</th>
                                <th>Change</th>
                                <th>Before</th>
                                <th>Balance after</th>
                                <th>Reference</th>
                                <th>By</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->created_at->format('M d, Y g:i A') }}</td>
                                    <td><span class="badge bg-{{ $transaction->type_color }}">{{ $transaction->type_label }}</span></td>
                                    <td class="fw-bold {{ $transaction->signed_change >= 0 ? 'text-success' : 'text-danger' }}">{{ $transaction->signed_change > 0 ? '+' : '' }}{{ $transaction->signed_change }}</td>
                                    <td>{{ $transaction->quantity_before }}</td>
                                    <td class="fw-bold">{{ $transaction->quantity_after }}</td>
                                    <td>{{ $transaction->reference_number ?? '—' }}</td>
                                    <td>{{ $transaction->user->name ?? '—' }}</td>
                                    <td><a href="{{ route('transactions.show', $transaction) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">No stock movements yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card text-center mb-3">
            <div class="card-body">
                <h6 class="text-muted">Quantity in Stock</h6>
                @if ($product->isOutOfStock())
                    <h1 class="text-danger">{{ $product->quantity }}</h1>
                    <span class="badge bg-danger">Out of Stock</span>
                @elseif ($product->isLowStock())
                    <h1 style="color: var(--theme-accent);">{{ $product->quantity }}</h1>
                    <span class="badge" style="background-color: var(--theme-accent);">Low Stock</span>
                @else
                    <h1 class="text-success">{{ $product->quantity }}</h1>
                    <span class="badge bg-success">In Stock</span>
                @endif
            </div>
        </div>

        <div class="d-grid gap-2">
            @can('admin')
            <a href="{{ route('adjustments.create') }}?product_id={{ $product->id }}" class="btn btn-outline-secondary">Adjust Stock</a>
            <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-primary">Edit Product</a>
            <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger w-100">Delete Product</button>
            </form>
            @endcan
        </div>
    </div>
</div>

@endsection