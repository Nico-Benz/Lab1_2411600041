@extends('layouts.grade-app')

@section('content')

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h2 id="greeting">{{ $greeting }}, {{ Auth::user()->name }}!</h2>
    <small class="text-muted" id="lastUpdated">Last updated: just now</small>
</div>

<!-- Stat Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stat-card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted">Average Grade</h5>
                <h2 class="card-text fw-bold" style="color: var(--theme-primary);" id="stat-average">{{ number_format($averageGrade, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted">Total Subjects</h5>
                <h2 class="card-text fw-bold text-success" id="stat-total">{{ $totalSubjects }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted">Completion Rate</h5>
                <h2 class="card-text fw-bold text-info"><span id="stat-completion">{{ $completionRate }}</span>%</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card text-center">
            <div class="card-body">
                <h5 class="card-title text-muted">Passed Subjects</h5>
                <h2 class="card-text fw-bold" style="color: var(--theme-accent);" id="stat-passed">{{ $passedCount }}</h2>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card"><div class="card-body chart-container"><canvas id="performanceChart"></canvas></div></div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card"><div class="card-body chart-container"><canvas id="statusChart"></canvas></div></div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card"><div class="card-body chart-container"><canvas id="categoryChart"></canvas></div></div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card"><div class="card-body chart-container"><canvas id="gradeTrendChart"></canvas></div></div>
    </div>
</div>

<!-- Alert -->
<div id="atRiskAlert" class="alert alert-warning {{ $belowThresholdCount > 0 ? '' : 'd-none' }}">
    ⚠ <span id="atRiskCount">{{ $belowThresholdCount }}</span> subject(s) at risk — check the Subjects page for details.
</div>

<!-- At Risk Subjects (live) -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">At Risk Subjects</h5>
    </div>
    <ul class="list-group list-group-flush" id="atRiskList">
        <li class="list-group-item text-muted">Loading...</li>
    </ul>
</div>

<!-- Recent Transactions (live) -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Transactions</h5>
        <a href="{{ route('grade-transactions.index') }}" class="small">View all</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr class="border-accent">
                        <th>Date/Time</th>
                        <th>Subject</th>
                        <th>Type</th>
                        <th class="text-end">Grade</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody id="recentTransactionsBody">
                    <tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recently Updated Subjects -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Recently Updated Subjects</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr class="border-accent">
                        <th>Code</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentItems as $item)
                        <tr class="{{ $item->isBelowThreshold() ? 'low-grade-row' : '' }}">
                            <td>{{ $item->subject_code }}</td>
                            <td>{{ $item->subject_name }}</td>
                            <td>{{ $item->category }}</td>
                            <td>
                                @if ($item->isPending())
                                    <span class="badge badge-pending">Pending</span>
                                @elseif ($item->isBelowThreshold())
                                    <span class="badge bg-danger">At Risk</span>
                                @else
                                    <span class="badge bg-success">Passed</span>
                                @endif
                            </td>
                            <td>{{ $item->current_grade > 0 ? number_format($item->current_grade, 2) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No subjects found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const categoryLabels = @json($categoryDistribution->keys());
    const categoryValues = @json($categoryDistribution->values());

    window.dashboardCharts = {};

    window.dashboardCharts.performance = new Chart(document.getElementById('performanceChart'), {
        type: 'bar',
        data: {
            labels: ['Avg Grade', 'Subjects', 'Completion %', 'Passed'],
            datasets: [{ label: 'Overview', data: [{{ $averageGrade }}, {{ $totalSubjects }}, {{ $completionRate }}, {{ $passedCount }}], backgroundColor: ['#231C35', '#2E8B57', '#6E5774', '#E67E22'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, title: { display: true, text: 'Performance Overview' } } }
    });

    window.dashboardCharts.status = new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Passed', 'Pending', 'At Risk'],
            datasets: [{ data: [{{ $passedCount }}, {{ $pendingCount }}, {{ $belowThresholdCount }}], backgroundColor: ['#2E8B57', '#E67E22', '#D9534F'] }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { title: { display: true, text: 'Status Distribution' } } }
    });

    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: { labels: categoryLabels, datasets: [{ label: 'Subjects per Category', data: categoryValues, backgroundColor: '#6E5774' }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, title: { display: true, text: 'Category Distribution' } } }
    });

    new Chart(document.getElementById('gradeTrendChart'), {
        type: 'line',
        data: {
            labels: @json($recentItems->pluck('subject_code')),
            datasets: [{ label: 'Grade', data: @json($recentItems->pluck('current_grade')), borderColor: '#231C35', backgroundColor: 'rgba(35, 28, 53, 0.15)', fill: true, tension: 0.3 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { title: { display: true, text: 'Recent Subject Grades' } }, scales: { y: { beginAtZero: true, max: 100 } } }
    });
</script>

<script>
    window.dashboardApi = {
        stats: @json(route('api.dashboard.stats')),
        atRisk: @json(route('api.grade-items.at-risk')),
        transactions: @json(route('api.transactions.recent')),
        interval: 30000
    };
</script>
<script src="{{ asset('js/dashboard-live.js') }}"></script>
@endsection