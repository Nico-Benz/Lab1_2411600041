@extends('layouts.grade-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Supplier Performance Report</h2>
    <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Back to Suppliers</a>
</div>

<!-- Summary Table -->
<div class="card mb-4">
    <div class="card-header fw-semibold">Performance Summary</div>
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Supplier (Instructor)</th>
                    <th class="text-end">Total Orders</th>
                    <th class="text-end">Delivered</th>
                    <th class="text-end">On Time</th>
                    <th class="text-end">On-Time Rate</th>
                    <th class="text-end">Avg Days (Actual / Quoted)</th>
                    <th class="text-end">Accuracy</th>
                    <th class="text-end">Total Value</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td><a href="{{ route('suppliers.show', $row['supplier_id']) }}">{{ $row['supplier_name'] }}</a></td>
                        <td class="text-end">{{ $row['total_orders'] }}</td>
                        <td class="text-end">{{ $row['delivered_orders'] }}</td>
                        <td class="text-end">{{ $row['on_time_orders'] }}</td>
                        <td class="text-end">
                            @if ($row['on_time_rate'] === null)
                                <span class="text-muted">—</span>
                            @else
                                <span class="badge {{ $row['on_time_rate'] >= 80 ? 'bg-success' : ($row['on_time_rate'] >= 50 ? 'bg-warning text-dark' : 'bg-danger') }}">{{ $row['on_time_rate'] }}%</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if ($row['avg_actual_days'] === null)
                                <span class="text-muted">—</span>
                            @else
                                {{ $row['avg_actual_days'] }} / {{ $row['avg_quoted_days'] ?? '—' }}
                            @endif
                        </td>
                        <td class="text-end">{{ $row['accuracy_rate'] !== null ? $row['accuracy_rate'].'%' : '—' }}</td>
                        <td class="text-end">₱{{ number_format($row['total_value'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No suppliers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Top and Bottom Suppliers -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Top Performing Suppliers</div>
            <ul class="list-group list-group-flush">
                @forelse ($top as $row)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $row['supplier_name'] }}
                        <span class="badge bg-success">{{ $row['on_time_rate'] }}% on time</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No delivered orders yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Bottom Performing Suppliers</div>
            <ul class="list-group list-group-flush">
                @forelse ($bottom as $row)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $row['supplier_name'] }}
                        <span class="badge bg-danger">{{ $row['on_time_rate'] }}% on time</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No delivered orders yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Average Delivery Days: Actual vs Quoted</div>
            <div class="card-body">
                @if ($daysChart->isEmpty())
                    <p class="text-muted mb-0">No delivered orders yet.</p>
                @else
                    <div style="position: relative; height: 300px;">
                        <canvas id="daysChart"></canvas>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Delivery Trend per Month (On Time vs Late)</div>
            <div class="card-body">
                @if ($trend->isEmpty())
                    <p class="text-muted mb-0">No delivered orders yet.</p>
                @else
                    <div style="position: relative; height: 300px;">
                        <canvas id="trendChart"></canvas>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const daysData = @json($daysChart);
    const trendData = @json($trend);

    const daysCanvas = document.getElementById('daysChart');
    if (daysCanvas && daysData.length) {
        new Chart(daysCanvas, {
            type: 'bar',
            data: {
                labels: daysData.map(d => d.name),
                datasets: [
                    { label: 'Actual days', data: daysData.map(d => d.actual), backgroundColor: '#E67E22' },
                    { label: 'Quoted days', data: daysData.map(d => d.quoted), backgroundColor: '#6E5774' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });
    }

    const trendCanvas = document.getElementById('trendChart');
    if (trendCanvas && trendData.length) {
        new Chart(trendCanvas, {
            type: 'bar',
            data: {
                labels: trendData.map(t => t.month),
                datasets: [
                    { label: 'On time', data: trendData.map(t => t.on_time), backgroundColor: '#2E8B57' },
                    { label: 'Late', data: trendData.map(t => t.late), backgroundColor: '#D9534F' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    }
</script>
@endsection