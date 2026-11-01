<div class="card stat-card text-center h-100">
    <div class="card-body">
        <h5 class="card-title text-muted">Pending Approvals</h5>
        <h2 class="card-text fw-bold" style="color: var(--theme-accent);" id="widget-pending-count">{{ $pendingCount }}</h2>
        <a href="{{ route('purchase-orders.index') }}" class="small">View purchase orders</a>
    </div>
</div>