@extends('layouts.grade-app')

@section('content')
<style>
    @media print {
        header, .sidebar, .no-print { display: none !important; }
        main { margin: 0 !important; width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Reports</h2>
    <button type="button" class="btn btn-outline-light no-print" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Print
    </button>
</div>

<form method="GET" action="{{ route('reports.index') }}" class="card card-body mb-3 no-print">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small mb-1">From</label>
            <input type="date" name="date_from" value="{{ $from }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">To</label>
            <input type="date" name="date_to" value="{{ $to }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary">Generate</button>
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
    </div>
    @if ($errors->any())
        <div class="text-danger small mt-2">{{ $errors->first() }}</div>
    @endif
</form>

<!-- Grade Summary -->
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Grade Summary</h5></div>
    <div class="card-body">
        <div class="row text-center g-3">
            <div class="col-md-3">
                <div class="text-muted small">Weighted average</div>
                <div class="fs-3 fw-bold">{{ number_format($summary['weightedAverage'], 2) }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Graded subjects</div>
                <div class="fs-3 fw-bold">{{ $summary['gradedSubjects'] }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Pending subjects</div>
                <div class="fs-3 fw-bold">{{ $summary['pendingSubjects'] }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Units graded</div>
                <div class="fs-3 fw-bold">{{ $summary['gradedUnits'] }}</div>
            </div>
        </div>
        <hr>
        <div class="row small">
            <div class="col-md-6">
                <span class="text-muted">Highest:</span>
                @if ($summary['highest'])
                    <strong>{{ $summary['highest']->subject_code }}</strong> {{ $summary['highest']->subject_name }}
                    ({{ number_format($summary['highest']->current_grade, 2) }})
                @else
                    -
                @endif
            </div>
            <div class="col-md-6">
                <span class="text-muted">Lowest:</span>
                @if ($summary['lowest'])
                    <strong>{{ $summary['lowest']->subject_code }}</strong> {{ $summary['lowest']->subject_name }}
                    ({{ number_format($summary['lowest']->current_grade, 2) }})
                @else
                    -
                @endif
            </div>
        </div>
        <div class="text-muted small mt-2">
            The weighted average counts each grade by its units, so a 3-unit subject weighs more than a 1-unit subject. Pending subjects are not included.
        </div>
    </div>
</div>

<!-- At Risk Report -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">At Risk Report</h5>
        <span class="badge bg-danger">{{ $atRisk->count() }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead>
                <tr class="border-accent">
                    <th>Code</th>
                    <th>Subject</th>
                    <th class="text-end">Grade</th>
                    <th class="text-end">Threshold</th>
                    <th class="text-end">Points to reach it</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($atRisk as $item)
                    <tr>
                        <td><a href="{{ route('grade-items.show', $item) }}" class="text-decoration-none">{{ $item->subject_code }}</a></td>
                        <td>{{ $item->subject_name }}</td>
                        <td class="text-end text-danger fw-semibold">{{ number_format($item->current_grade, 2) }}</td>
                        <td class="text-end">{{ number_format($item->low_grade_threshold, 2) }}</td>
                        <td class="text-end">{{ number_format($item->low_grade_threshold - $item->current_grade, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">No subjects at risk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Grade Movement Report -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Grade Movement Report</h5>
        <div class="small text-muted">{{ \Carbon\Carbon::parse($from)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}</div>
    </div>
    <div class="card-body pb-0">
        <span class="badge bg-secondary me-1">Total: {{ $movementSummary['total'] }}</span>
        <span class="badge bg-success me-1">Increase: {{ $movementSummary['increase'] }}</span>
        <span class="badge bg-danger me-1">Decrease: {{ $movementSummary['decrease'] }}</span>
        <span class="badge bg-warning text-dark">Adjustment: {{ $movementSummary['adjustment'] }}</span>
    </div>
    <div class="table-responsive mt-3">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead>
                <tr class="border-accent">
                    <th>Date/Time</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th class="text-end">Change</th>
                    <th class="text-end">Grade</th>
                    <th>Reference</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($movements as $t)
                    @php
                        $badge = match ($t->type) {
                            'increase' => 'success',
                            'decrease' => 'danger',
                            default => 'warning',
                        };
                    @endphp
                    <tr>
                        <td class="text-nowrap">{{ $t->created_at->format('M d, Y h:i A') }}</td>
                        <td>{{ $t->gradeItem->subject_code }} <span class="small text-muted">{{ $t->gradeItem->subject_name }}</span></td>
                        <td><span class="badge bg-{{ $badge }}">{{ ucfirst($t->type) }}</span></td>
                        <td class="text-end fw-semibold">
                            @if ($t->type === 'increase')
                                <span class="text-success">+{{ number_format($t->quantity, 2) }}</span>
                            @elseif ($t->type === 'decrease')
                                <span class="text-danger">-{{ number_format($t->quantity, 2) }}</span>
                            @else
                                <span class="text-warning">Set</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">{{ number_format($t->previous_grade, 2) }} &rarr; {{ number_format($t->new_grade, 2) }}</td>
                        <td>{{ $t->reference_number ?? '-' }}</td>
                        <td>{{ $t->user->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">No transactions in this date range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection