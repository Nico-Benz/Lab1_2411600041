@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Supplier Performance</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('exports.download', ['report' => 'supplier-performance', 'format' => 'csv']) }}" class="btn btn-outline-secondary">Export CSV</a>
        <a href="{{ route('exports.download', ['report' => 'supplier-performance', 'format' => 'pdf']) }}" class="btn btn-outline-secondary">Export PDF</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h5 class="card-title">Delivery trend (last 6 months)</h5>
                <canvas id="trendChart" height="160"></canvas>
                <div class="small text-muted mt-2">Based on received orders. "On time" means received on or before the expected delivery date.</div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h5 class="card-title">On-time delivery by supplier</h5>
                <canvas id="supplierChart" height="160"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card stat-card h-100 border-success">
            <div class="card-body">
                <h5 class="card-title text-success">Top performing suppliers</h5>
                <ol class="mb-0">
                    @forelse ($top as $row)
                        <li>
                            <a href="{{ route('suppliers.show', $row['supplier']) }}">{{ $row['supplier']->name }}</a>
                            — {{ $row['on_time_rate'] }}% on time, {{ $row['accuracy_rate'] }}% accuracy
                        </li>
                    @empty
                        <li class="text-muted list-unstyled">No delivered orders yet.</li>
                    @endforelse
                </ol>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card stat-card h-100 border-danger">
            <div class="card-body">
                <h5 class="card-title text-danger">Needs attention</h5>
                <ol class="mb-0">
                    @forelse ($bottom as $row)
                        <li>
                            <a href="{{ route('suppliers.show', $row['supplier']) }}">{{ $row['supplier']->name }}</a>
                            — {{ $row['on_time_rate'] }}% on time, {{ $row['accuracy_rate'] }}% accuracy
                        </li>
                    @empty
                        <li class="text-muted list-unstyled">Shown when there are more than 3 suppliers with deliveries.</li>
                    @endforelse
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="card stat-card">
    <div class="card-body">
        <h5 class="card-title">All suppliers</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th class="text-center">Orders placed</th>
                        <th class="text-center">Delivered</th>
                        <th class="text-end">On time</th>
                        <th class="text-end">Avg days</th>
                        <th class="text-end">Quoted days</th>
                        <th class="text-end">Accuracy</th>
                        <th class="text-end">Total value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td><a href="{{ route('suppliers.show', $row['supplier']) }}">{{ $row['supplier']->name }}</a></td>
                            <td class="text-center">{{ $row['total_orders'] }}</td>
                            <td class="text-center">{{ $row['delivered_orders'] }}</td>
                            <td class="text-end">{{ $row['on_time_rate'] !== null ? $row['on_time_rate'] . '%' : '—' }}</td>
                            <td class="text-end">{{ $row['avg_actual_days'] ?? '—' }}</td>
                            <td class="text-end">{{ $row['avg_quoted_days'] ?? '—' }}</td>
                            <td class="text-end">{{ $row['accuracy_rate'] !== null ? $row['accuracy_rate'] . '%' : '—' }}</td>
                            <td class="text-end">₱{{ number_format($row['total_value'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@section('scripts')
@php
    $supplierChart = $rows->map(function ($row) {
        return ['name' => $row['supplier']->name, 'rate' => $row['on_time_rate']];
    })->values();
@endphp
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const trend = @json($trend);
    const suppliers = @json($supplierChart);

    new Chart(document.getElementById('trendChart'), {
        data: {
            labels: trend.labels,
            datasets: [
                { type: 'line', label: 'On-time %', data: trend.on_time_rates, borderColor: '#E67E22', backgroundColor: '#E67E22', yAxisID: 'y', spanGaps: true, tension: 0.3 },
                { type: 'bar', label: 'Avg delivery days', data: trend.average_days, backgroundColor: 'rgba(35, 28, 53, 0.6)', yAxisID: 'y1' }
            ]
        },
        options: {
            scales: {
                y: { min: 0, max: 100, title: { display: true, text: 'On-time %' } },
                y1: { position: 'right', min: 0, grid: { drawOnChartArea: false }, title: { display: true, text: 'Days' } }
            }
        }
    });

    new Chart(document.getElementById('supplierChart'), {
        type: 'bar',
        data: {
            labels: suppliers.map(s => s.name),
            datasets: [{ label: 'On-time %', data: suppliers.map(s => s.rate), backgroundColor: '#E67E22' }]
        },
        options: { indexAxis: 'y', scales: { x: { min: 0, max: 100 } }, plugins: { legend: { display: false } } }
    });
</script>
@endsection
