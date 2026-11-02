@extends('layouts.grade-app')

@section('content')

@php
    $subjectOptions = $gradeItems->mapWithKeys(fn ($i) => [
        $i->id => $i->subject_code . ' - ' . $i->subject_name,
    ])->all();

    $typeOptions = collect($types)->mapWithKeys(fn ($t) => [$t => ucfirst($t)])->all();

    $userOptions = $users->pluck('name', 'id')->all();

    $filterFields = [
        ['name' => 'grade_item_id', 'label' => 'Subject', 'type' => 'select', 'col' => 2, 'options' => $subjectOptions],
        ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'col' => 2, 'options' => $typeOptions],
        ['name' => 'user_id', 'label' => 'By (User)', 'type' => 'select', 'col' => 2, 'options' => $userOptions],
        ['name' => 'date_from', 'label' => 'From', 'type' => 'date', 'col' => 2],
        ['name' => 'date_to', 'label' => 'To', 'type' => 'date', 'col' => 2],
    ];
@endphp
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

<x-filter-form :action="route('grade-transactions.index')" :fields="$filterFields" />

<div class="d-flex justify-content-end gap-2 mb-3">
    <a href="{{ route('exports.download', ['report' => 'transactions', 'format' => 'csv'] + request()->query()) }}"
       class="btn btn-sm btn-outline-light">
        <i class="bi bi-filetype-csv me-1"></i>Export CSV
    </a>
    <a href="{{ route('exports.download', ['report' => 'transactions', 'format' => 'pdf'] + request()->query()) }}"
       class="btn btn-sm btn-outline-light">
        <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
    </a>
</div>

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