@extends('layouts.grade-app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Grade Transactions</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('grade-adjustments.create') }}" class="btn btn-outline-light">
            <i class="bi bi-sliders me-1"></i>Adjust Grade
        </a>
        <a href="{{ route('grade-transactions.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Record Transaction
        </a>
    </div>
</div>

<form method="GET" action="{{ route('grade-transactions.index') }}" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small mb-1">Subject</label>
            <select name="grade_item_id" class="form-select form-select-sm">
                <option value="">All subjects</option>
                @foreach ($gradeItems as $item)
                    <option value="{{ $item->id }}" @selected(request('grade_item_id') == $item->id)>
                        {{ $item->subject_code }} - {{ $item->subject_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Type</label>
            <select name="type" class="form-select form-select-sm">
                <option value="">All types</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            <a href="{{ route('grade-transactions.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date/Time</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th class="text-end">Change</th>
                    <th class="text-end">Grade</th>
                    <th>Reference</th>
                    <th>By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $t)
                    @php
                        $badge = match ($t->type) {
                            'increase' => 'success',
                            'decrease' => 'danger',
                            default => 'warning',
                        };
                    @endphp
                    <tr>
                        <td class="text-nowrap">{{ $t->created_at->format('M d, Y h:i A') }}</td>
                        <td>
                            <a href="{{ route('grade-items.show', $t->gradeItem) }}" class="text-decoration-none">
                                {{ $t->gradeItem->subject_code }}
                            </a>
                            <div class="small text-muted">{{ $t->gradeItem->subject_name }}</div>
                        </td>
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
                        <td class="text-end text-nowrap">
                            {{ number_format($t->previous_grade, 2) }} &rarr; {{ number_format($t->new_grade, 2) }}
                        </td>
                        <td>{{ $t->reference_number ?? '-' }}</td>
                        <td>{{ $t->user->name }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('grade-transactions.show', $t) }}" class="btn btn-sm btn-outline-primary">View</a>
                            <form method="POST" action="{{ route('grade-transactions.destroy', $t) }}" class="d-inline"
                                  onsubmit="return confirm('Delete this transaction and restore the previous grade?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No transactions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $transactions->links('pagination::bootstrap-5') }}
</div>
@endsection