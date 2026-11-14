{{-- Needs $recentOrders (bound by the View Composer) --}}
<div class="card stat-card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="card-title mb-0">Recent Purchase Orders</h5>
            <a href="{{ route('purchase-orders.index') }}" class="small">View all</a>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Reference</th><th>Supplier</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                <tbody id="recentOrdersBody">
                    @forelse ($recentOrders as $order)
                        <tr>
                            <td><a href="{{ route('purchase-orders.show', $order) }}">{{ $order->reference_number }}</a></td>
                            <td>{{ $order->supplier->name ?? '—' }}</td>
                            <td><span class="badge bg-{{ $order->status_color }}">{{ ucfirst($order->status) }}</span></td>
                            <td class="text-end">₱{{ number_format((float) $order->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No purchase orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
