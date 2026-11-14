{{-- Needs $topSuppliers (bound by the View Composer, cached for 60 seconds) --}}
<div class="card stat-card h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="card-title mb-0">Top Suppliers</h5>
            <a href="{{ route('reports.supplier-performance') }}" class="small">Full report</a>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Supplier</th><th class="text-end">On time</th><th class="text-end">Accuracy</th></tr></thead>
                <tbody id="supplierPerfBody">
                    @forelse ($topSuppliers as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="text-end">{{ $row['on_time_rate'] !== null ? $row['on_time_rate'] . '%' : '—' }}</td>
                            <td class="text-end">{{ $row['accuracy_rate'] !== null ? $row['accuracy_rate'] . '%' : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">No delivered orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
