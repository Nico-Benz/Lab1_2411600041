<div class="card stat-card h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Purchase Orders</h5>
        <a href="{{ route('purchase-orders.index') }}" class="small">View all</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody id="widget-recent-orders-body">
                    @forelse ($recentOrders as $order)
                        @php
                            $badge = match ($order->status) {
                                'draft' => 'secondary',
                                'pending' => 'warning',
                                'approved' => 'info',
                                'sent' => 'primary',
                                'received' => 'success',
                                'rejected' => 'danger',
                                default => 'dark',
                            };
                        @endphp
                        <tr>
                            <td><a href="{{ route('purchase-orders.show', $order) }}">{{ $order->reference_number }}</a></td>
                            <td>{{ $order->supplier?->company_name ?? '—' }}</td>
                            <td><span class="badge bg-{{ $badge }}">{{ ucfirst($order->status) }}</span></td>
                            <td class="text-end">₱{{ number_format($order->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No purchase orders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>