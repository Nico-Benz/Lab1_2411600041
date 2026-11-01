<div class="card stat-card h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Reorder Alerts</h5>
        <span class="badge bg-danger" id="widget-reorder-count">{{ $reorderAlerts->count() }}</span>
    </div>
    <ul class="list-group list-group-flush" id="widget-reorder-alerts-list">
        @forelse ($reorderAlerts as $row)
            <li class="list-group-item">
                <div class="d-flex justify-content-between">
                    <strong>{{ $row['code'] }}</strong>
                    <span class="text-danger">{{ number_format($row['grade'], 2) }}</span>
                </div>
                <div class="small text-muted">
                    {{ $row['name'] }}<br>
                    Reorder point: {{ $row['reorder_point'] }} &middot; Instructor: {{ $row['supplier'] }}
                </div>
            </li>
        @empty
            <li class="list-group-item text-muted">No subjects need a reorder.</li>
        @endforelse
    </ul>
</div>