{{-- Needs $reorderAlerts (bound by the View Composer, cached for 60 seconds) --}}
<div class="card stat-card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="card-title mb-0">Reorder Alerts</h5>
            <a href="{{ route('purchase-orders.index', ['status' => 'draft']) }}" class="small">Draft orders</a>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Product</th><th class="text-end">Stock / Reorder pt.</th><th class="text-end">Suggested</th><th></th></tr></thead>
                <tbody id="reorderAlertsBody">
                    @forelse ($reorderAlerts as $alert)
                        <tr>
                            <td>
                                <a href="{{ route('products.show', $alert['id']) }}">{{ $alert['name'] }}</a>
                                <div class="small text-muted">{{ $alert['sku'] }} · {{ $alert['supplier'] ?? 'No supplier' }}</div>
                            </td>
                            <td class="text-end">{{ $alert['quantity'] }} / {{ $alert['reorder_point'] }}</td>
                            <td class="text-end">{{ $alert['suggested'] }}</td>
                            <td>
                                @if ($alert['has_open_order'])
                                    <span class="badge bg-info">On order</span>
                                @else
                                    <span class="badge bg-danger">Reorder</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">All products are above their reorder point.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
