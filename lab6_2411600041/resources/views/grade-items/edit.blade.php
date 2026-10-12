@extends('layouts.grade-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Edit Subject</h2>
    <a href="{{ route('grade-items.index') }}" class="btn btn-outline-secondary">← Back to Subjects</a>
</div>

<div class="card stat-card">
    <div class="card-body">

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('grade-items.update', $gradeItem) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Subject Code</label>
                    <input type="text" name="subject_code" class="form-control" value="{{ old('subject_code', $gradeItem->subject_code) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select" required>
                        @foreach (['Major', 'Minor', 'Laboratory', 'General Education'] as $cat)
                            <option value="{{ $cat }}" {{ old('category', $gradeItem->category) == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Subject Name</label>
                <input type="text" name="subject_name" class="form-control" value="{{ old('subject_name', $gradeItem->subject_name) }}" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2">{{ old('description', $gradeItem->description) }}</textarea>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Current Grade</label>
                    <input type="number" step="0.01" min="0" max="100" name="current_grade" class="form-control" value="{{ old('current_grade', $gradeItem->current_grade) }}" required>
                    @if ($gradeItem->isPending())
                        <small class="text-muted">This subject is currently pending — update the grade once available.</small>
                    @endif
                </div>
                <div class="col-md-3">
                    <label class="form-label">Low Grade Threshold</label>
                    <input type="number" step="0.01" min="0" max="100" name="low_grade_threshold" class="form-control" value="{{ old('low_grade_threshold', $gradeItem->low_grade_threshold) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Units</label>
                    <input type="number" min="1" max="10" name="units" class="form-control" value="{{ old('units', $gradeItem->units) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Instructor</label>
                    <input type="text" name="instructor" class="form-control" value="{{ old('instructor', $gradeItem->instructor) }}">
                </div>
            </div>

            <button type="submit" class="btn" style="background-color: var(--theme-primary); color: white;">Update Subject</button>
            <a href="{{ route('grade-items.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>

    </div>
</div>

@endsection