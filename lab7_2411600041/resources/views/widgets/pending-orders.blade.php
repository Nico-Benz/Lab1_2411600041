{{-- Needs $pendingCount, $pendingValue, $pendingOrders (bound by the View Composer) --}}
<div class="card stat-card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <h5 class="card-title mb-0">Pending Approval</h5>
            <a href="{{ route('purchase-orders.index', ['status' => 'pending']) }}" class="small">View all</a>
        </div>
        <div class="d-flex align-items-baseline gap-3 my-2">
            <span class="display-6 fw-bold" id="pendingCount">{{ $pendingCount }}</span>
            <span class="text-muted">order(s) worth <strong id="pendingValue">₱{{ number_format($pendingValue, 2) }}</strong></span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Reference</th><th>Supplier</th><th class="text-end">Total</th></tr></thead>
                <tbody id="pendingOrdersBody">
                    @forelse ($pendingOrders as $order)
                        <tr>
                            <td><a href="{{ route('purchase-orders.show', $order) }}">{{ $order->reference_number }}</a></td>
                            <td>{{ $order->supplier->name ?? '—' }}</td>
                            <td class="text-end">₱{{ number_format((float) $order->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">Nothing is waiting for approval.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
