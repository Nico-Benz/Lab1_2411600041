@extends('layouts.grade-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>{{ $gradeItem->subject_code }} — {{ $gradeItem->subject_name }}</h2>
    <a href="{{ route('grade-items.index') }}" class="btn btn-outline-secondary">← Back to Subjects</a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card stat-card mb-4">
            <div class="card-body">
                <h5 class="card-title text-muted mb-3">Subject Details</h5>

                <div class="row mb-2">
                    <div class="col-4 text-muted">Subject Code</div>
                    <div class="col-8">{{ $gradeItem->subject_code }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Subject Name</div>
                    <div class="col-8">{{ $gradeItem->subject_name }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Description</div>
                    <div class="col-8">{{ $gradeItem->description ?? '—' }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Category</div>
                    <div class="col-8">{{ $gradeItem->category }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Units</div>
                    <div class="col-8">{{ $gradeItem->units }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Instructor</div>
                    <div class="col-8">{{ $gradeItem->instructor ?? '—' }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 text-muted">Low Grade Threshold</div>
                    <div class="col-8">{{ number_format($gradeItem->low_grade_threshold, 2) }}</div>
                </div>
                <div class="row">
                    <div class="col-4 text-muted">Last Updated</div>
                    <div class="col-8">{{ $gradeItem->updated_at->format('M d, Y g:i A') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stat-card text-center mb-3">
            <div class="card-body">
                <h6 class="text-muted">Current Grade</h6>
                @if ($gradeItem->isPending())
                    <h1><span class="badge badge-pending">Pending</span></h1>
                @elseif ($gradeItem->isBelowThreshold())
                    <h1 class="text-danger">{{ number_format($gradeItem->current_grade, 2) }}</h1>
                    <span class="badge bg-danger">Below Threshold</span>
                @else
                    <h1 class="text-success">{{ number_format($gradeItem->current_grade, 2) }}</h1>
                    <span class="badge bg-success">Passed</span>
                @endif
            </div>
        </div>

        <div class="d-grid gap-2">
            <a href="{{ route('grade-items.edit', $gradeItem) }}" class="btn btn-outline-primary">Edit Subject</a>
            <form action="{{ route('grade-items.destroy', $gradeItem) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this subject?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger w-100">Delete Subject</button>
            </form>
        </div>
    </div>
</div>

@endsection