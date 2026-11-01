<div class="card stat-card h-100">
    <div class="card-header">
        <h5 class="mb-0">Top Suppliers (On-Time Delivery)</h5>
    </div>
    <ul class="list-group list-group-flush" id="widget-supplier-performance-list">
        @forelse ($topSuppliers as $row)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <a href="{{ route('suppliers.show', $row['id']) }}">{{ $row['name'] }}</a>
                <span class="badge {{ $row['rating'] >= 80 ? 'bg-success' : ($row['rating'] >= 50 ? 'bg-warning text-dark' : 'bg-danger') }}">
                    {{ number_format($row['rating'], 1) }}%
                </span>
            </li>
        @empty
            <li class="list-group-item text-muted">No delivered orders yet.</li>
        @endforelse
    </ul>
</div>