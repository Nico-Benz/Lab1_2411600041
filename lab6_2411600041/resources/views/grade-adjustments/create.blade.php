@extends('layouts.grade-app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Adjust Grade</h2>
    <a href="{{ route('grade-transactions.index') }}" class="btn btn-outline-light">Back to list</a>
</div>

<div class="card">
    <div class="card-body">
        <p class="text-muted">
            Use this when the grade in the system is wrong (for example a recount or an encoding error).
            Enter the correct grade and the reason. The system records the change as an adjustment, so the old and new grade stay in the history.
        </p>

        <form method="POST" action="{{ route('grade-adjustments.store') }}">
            @csrf

            <div class="mb-3">
                <label for="grade_item_id" class="form-label">Subject</label>
                <select name="grade_item_id" id="grade_item_id"
                        class="form-select @error('grade_item_id') is-invalid @enderror" required>
                    <option value="">Select a subject</option>
                    @foreach ($gradeItems as $item)
                        <option value="{{ $item->id }}"
                                data-grade="{{ $item->current_grade }}"
                                @selected(old('grade_item_id') == $item->id)>
                            {{ $item->subject_code }} - {{ $item->subject_name }}
                        </option>
                    @endforeach
                </select>
                @error('grade_item_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="system_grade" class="form-label">Grade in the system</label>
                <input type="text" id="system_grade" class="form-control" value="-" readonly>
            </div>

            <div class="mb-3">
                <label for="physical_grade" class="form-label">Correct grade</label>
                <input type="number" name="physical_grade" id="physical_grade" step="0.01" min="0" max="100"
                       value="{{ old('physical_grade') }}"
                       class="form-control @error('physical_grade') is-invalid @enderror" required>
                @error('physical_grade')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div id="differencePreview" class="form-text fw-semibold"></div>
            </div>

            <div class="mb-3">
                <label for="reason" class="form-label">Reason for adjustment</label>
                <select name="reason" id="reason" class="form-select @error('reason') is-invalid @enderror" required>
                    <option value="">Select a reason</option>
                    @foreach ($reasons as $key => $label)
                        <option value="{{ $key }}" @selected(old('reason') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('reason')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">Notes (optional)</label>
                <textarea name="notes" id="notes" rows="3" maxlength="500"
                          class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save Adjustment</button>
                <a href="{{ route('grade-transactions.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const subjectSelect = document.getElementById('grade_item_id');
    const systemGrade = document.getElementById('system_grade');
    const physicalInput = document.getElementById('physical_grade');
    const differencePreview = document.getElementById('differencePreview');

    function updateForm() {
        const option = subjectSelect.options[subjectSelect.selectedIndex];

        if (!option || !option.value) {
            systemGrade.value = '-';
            differencePreview.textContent = '';
            return;
        }

        const current = parseFloat(option.dataset.grade);
        systemGrade.value = current.toFixed(2);

        const entered = parseFloat(physicalInput.value);
        if (isNaN(entered)) {
            differencePreview.textContent = '';
            return;
        }

        const diff = entered - current;
        if (diff === 0) {
            differencePreview.className = 'form-text fw-semibold text-muted';
            differencePreview.textContent = 'No difference. The grade is already ' + current.toFixed(2) + '.';
        } else {
            differencePreview.className = 'form-text fw-semibold ' + (diff > 0 ? 'text-success' : 'text-danger');
            differencePreview.textContent = 'Difference: ' + (diff > 0 ? '+' : '') + diff.toFixed(2);
        }
    }

    subjectSelect.addEventListener('change', updateForm);
    physicalInput.addEventListener('input', updateForm);
    updateForm();
</script>
@endsection