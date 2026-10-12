@extends('layouts.grade-app')

@section('content')
@php
    $badge = match ($transaction->type) {
        'increase' => 'success',
        'decrease' => 'danger',
        default => 'warning',
    };
    $item = $transaction->gradeItem;
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Transaction #{{ $transaction->id }}</h2>
    <a href="{{ route('grade-transactions.index') }}" class="btn btn-outline-light">Back to list</a>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Transaction Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Type</dt>
                    <dd class="col-sm-7"><span class="badge bg-{{ $badge }}">{{ ucfirst($transaction->type) }}</span></dd>

                    <dt class="col-sm-5">Amount</dt>
                    <dd class="col-sm-7">{{ number_format($transaction->quantity, 2) }}</dd>

                    <dt class="col-sm-5">Grade before</dt>
                    <dd class="col-sm-7">{{ number_format($transaction->previous_grade, 2) }}</dd>

                    <dt class="col-sm-5">Grade after</dt>
                    <dd class="col-sm-7">{{ number_format($transaction->new_grade, 2) }}</dd>

                    <dt class="col-sm-5">Reference</dt>
                    <dd class="col-sm-7">{{ $transaction->reference_number ?? '-' }}</dd>

                    <dt class="col-sm-5">Notes</dt>
                    <dd class="col-sm-7">{{ $transaction->notes ?? '-' }}</dd>

                    <dt class="col-sm-5">Recorded on</dt>
                    <dd class="col-sm-7">{{ $transaction->created_at->format('M d, Y h:i A') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-6 d-flex flex-column gap-3">
        <div class="card">
            <div class="card-header fw-semibold">Subject</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Code</dt>
                    <dd class="col-sm-7">
                        <a href="{{ route('grade-items.show', $item) }}" class="text-decoration-none">{{ $item->subject_code }}</a>
                    </dd>

                    <dt class="col-sm-5">Name</dt>
                    <dd class="col-sm-7">{{ $item->subject_name }}</dd>

                    <dt class="col-sm-5">Category</dt>
                    <dd class="col-sm-7">{{ $item->category }}</dd>

                    <dt class="col-sm-5">Current grade</dt>
                    <dd class="col-sm-7">
                        {{ number_format($item->current_grade, 2) }}
                        <span class="badge bg-{{ $item->grade_status_color }} ms-1">{{ $item->grade_status }}</span>
                    </dd>

                    <dt class="col-sm-5">Threshold</dt>
                    <dd class="col-sm-7">{{ number_format($item->low_grade_threshold, 2) }}</dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header fw-semibold">Recorded By</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Name</dt>
                    <dd class="col-sm-7">{{ $transaction->user->name }}</dd>

                    <dt class="col-sm-5">Email</dt>
                    <dd class="col-sm-7">{{ $transaction->user->email }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <a href="{{ route('grade-transactions.index') }}" class="btn btn-outline-light">Back to list</a>
    <form method="POST" action="{{ route('grade-transactions.destroy', $transaction) }}"
          onsubmit="return confirm('Delete this transaction and restore the previous grade?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Delete</button>
    </form>
</div>
@endsection