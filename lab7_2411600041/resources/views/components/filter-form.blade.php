{{--
    Reusable filter form. Use it in any list page:

    <x-filter-form :action="route('purchase-orders.index')" :fields="$filterFields">
        (optional buttons, e.g. export links)
    </x-filter-form>

    Every field is an array: name, label, type (text | date | select), col (1-12),
    placeholder, and options (value => label) for selects.
    The values come back from the URL (request()), so the form keeps what you typed.
--}}
@props(['action', 'fields' => [], 'reset' => null])

<div class="card stat-card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ $action }}" class="row g-2 align-items-end">
            @foreach ($fields as $field)
                @php
                    $fieldName = $field['name'];
                    $fieldType = $field['type'] ?? 'text';
                    $fieldCol = $field['col'] ?? 3;
                @endphp
                <div class="col-md-{{ $fieldCol }}">
                    <label class="form-label small">{{ $field['label'] }}</label>
                    @if ($fieldType === 'select')
                        <select name="{{ $fieldName }}" class="form-select">
                            <option value="">{{ $field['placeholder'] ?? 'All' }}</option>
                            @foreach ($field['options'] as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" {{ (string) request($fieldName) === (string) $optionValue ? 'selected' : '' }}>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="{{ $fieldType }}" name="{{ $fieldName }}" class="form-control" value="{{ request($fieldName) }}" placeholder="{{ $field['placeholder'] ?? '' }}">
                    @endif
                </div>
            @endforeach
            <div class="col-auto d-flex gap-2">
                <button type="submit" class="btn btn-dark">Filter</button>
                <a href="{{ $reset ?? $action }}" class="btn btn-outline-secondary">Reset</a>
                {{ $slot }}
            </div>
        </form>
    </div>
</div>
