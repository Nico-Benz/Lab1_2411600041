@props(['action', 'fields' => []])

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ $action }}" class="row g-2 align-items-end">
            @foreach ($fields as $field)
                <div class="col-md-{{ $field['col'] ?? 3 }}">
                    <label class="form-label small">{{ $field['label'] }}</label>

                    @if (($field['type'] ?? 'text') === 'select')
                        <select name="{{ $field['name'] }}" class="form-select">
                            <option value="">All</option>
                            @foreach ($field['options'] as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" @selected((string) request($field['name']) === (string) $optionValue)>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @else
                        <input
                            type="{{ $field['type'] ?? 'text' }}"
                            name="{{ $field['name'] }}"
                            value="{{ request($field['name']) }}"
                            class="form-control"
                            placeholder="{{ $field['placeholder'] ?? '' }}">
                    @endif
                </div>
            @endforeach

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn flex-grow-1" style="background-color: var(--theme-primary); color: white;">Apply</button>
                <a href="{{ $action }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>