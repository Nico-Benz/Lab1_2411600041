@extends('layouts.grade-app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Record Transaction</h2>
    <a href="{{ route('grade-transactions.index') }}" class="btn btn-outline-light">Back to list</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('grade-transactions.store') }}">
            @csrf

            <div class="mb-3">
                <label for="grade_item_id" class="form-label">Subject</label>
                <select name="grade_item_id" id="grade_item_id"
                        class="form-select @error('grade_item_id') is-invalid @enderror" required>
                    <option value="">Select a subject</option>
                    @foreach ($gradeItems as $item)
                        <option value="{{ $item->id }}"
                                data-grade="{{ $item->current_grade }}"
                                data-threshold="{{ $item->low_grade_threshold }}"
                                @selected(old('grade_item_id') == $item->id)>
                            {{ $item->subject_code }} - {{ $item->subject_name }}
                        </option>
                    @endforeach
                </select>
                @error('grade_item_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div id="gradeInfo" class="form-text"></div>
            </div>

            <div class="mb-3">
                <label class="form-label d-block">Transaction Type</label>
                @foreach ($types as $type)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="type"
                               id="type-{{ $type }}" value="{{ $type }}"
                               @checked(old('type', 'decrease') === $type)>
                        <label class="form-check-label" for="type-{{ $type }}">{{ ucfirst($type) }}</label>
                    </div>
                @endforeach
                @error('type')
                    <div class="text-danger small">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="quantity" id="quantityLabel" class="form-label">Amount</label>
                <input type="number" name="quantity" id="quantity" step="0.01"
                       value="{{ old('quantity') }}"
                       class="form-control @error('quantity') is-invalid @enderror" required>
                @error('quantity')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div id="quantityHint" class="form-text"></div>
                <div id="preview" class="form-text fw-semibold"></div>
            </div>

            <div class="mb-3">
                <label for="reference_number" class="form-label">Reference Number (optional)</label>
                <input type="text" name="reference_number" id="reference_number" maxlength="50"
                       value="{{ old('reference_number') }}"
                       class="form-control @error('reference_number') is-invalid @enderror">
                @error('reference_number')
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
                <button type="submit" class="btn btn-primary">Save Transaction</button>
                <a href="{{ route('grade-transactions.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const subjectSelect = document.getElementById('grade_item_id');
    const quantityInput = document.getElementById('quantity');
    const quantityLabel = document.getElementById('quantityLabel');
    const quantityHint = document.getElementById('quantityHint');
    const gradeInfo = document.getElementById('gradeInfo');
    const preview = document.getElementById('preview');
    const typeRadios = document.querySelectorAll('input[name="type"]');

    function selectedType() {
        const checked = document.querySelector('input[name="type"]:checked');
        return checked ? checked.value : 'decrease';
    }

    function refreshForm() {
        const option = subjectSelect.options[subjectSelect.selectedIndex];
        const type = selectedType();

        if (!option || !option.value) {
            gradeInfo.textContent = '';
            quantityHint.textContent = '';
            preview.textContent = '';
            return;
        }

        const grade = parseFloat(option.dataset.grade);
        gradeInfo.textContent = 'Current grade: ' + grade.toFixed(2);

        let min = 0.01;
        let max = 100;

        if (type === 'decrease') {
            max = grade;
            quantityLabel.textContent = 'Amount to subtract';
            quantityHint.textContent = grade > 0
                ? 'Maximum: ' + grade.toFixed(2) + ' (a grade cannot go below 0)'
                : 'This subject has no grade yet, so nothing can be subtracted.';
        } else if (type === 'increase') {
            max = 100 - grade;
            quantityLabel.textContent = 'Amount to add';
            quantityHint.textContent = max > 0
                ? 'Maximum: ' + max.toFixed(2) + ' (a grade cannot go above 100)'
                : 'This subject is already at 100.';
        } else {
            min = 0;
            quantityLabel.textContent = 'New grade';
            quantityHint.textContent = 'Sets the grade directly (0 to 100), like a corrected record.';
        }

        quantityInput.min = min;
        quantityInput.max = max.toFixed(2);
        updatePreview();
    }

    function updatePreview() {
        const option = subjectSelect.options[subjectSelect.selectedIndex];
        const amount = parseFloat(quantityInput.value);

        if (!option || !option.value || isNaN(amount)) {
            preview.textContent = '';
            return;
        }

        const grade = parseFloat(option.dataset.grade);
        const threshold = parseFloat(option.dataset.threshold);
        const type = selectedType();
        let result = grade;

        if (type === 'increase') result = grade + amount;
        if (type === 'decrease') result = grade - amount;
        if (type === 'adjustment') result = amount;

        if (result < 0 || result > 100) {
            preview.className = 'form-text fw-semibold text-danger';
            preview.textContent = 'Result would be ' + result.toFixed(2) + ', which is not allowed.';
            return;
        }

        let status = 'Passed';
        if (result === 0) status = 'Pending';
        else if (result < threshold) status = 'At Risk';

        preview.className = 'form-text fw-semibold ' + (status === 'At Risk' ? 'text-danger' : 'text-success');
        preview.textContent = 'Resulting grade: ' + result.toFixed(2) + ' (' + status + ')';
    }

    subjectSelect.addEventListener('change', refreshForm);
    quantityInput.addEventListener('input', updatePreview);
    typeRadios.forEach(function (radio) {
        radio.addEventListener('change', refreshForm);
    });

    refreshForm();
</script>
@endsection