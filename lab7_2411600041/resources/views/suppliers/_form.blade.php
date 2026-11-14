{{-- Shared by suppliers/create and suppliers/edit. $supplier exists only when editing. --}}
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row mb-3">
    <div class="col-md-6">
        <label class="form-label">Company Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $supplier->name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Contact Person</label>
        <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $supplier->contact_person ?? '') }}" required>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $supplier->email ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $supplier->phone ?? '') }}" placeholder="0917 123 4567" required>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Address</label>
    <textarea name="address" class="form-control" rows="2">{{ old('address', $supplier->address ?? '') }}</textarea>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <label class="form-label">Payment Terms</label>
        <input type="text" name="payment_terms" class="form-control" list="paymentTermsList" value="{{ old('payment_terms', $supplier->payment_terms ?? 'Net 30') }}" required>
        <datalist id="paymentTermsList">
            <option value="COD"></option>
            <option value="Net 15"></option>
            <option value="Net 30"></option>
            <option value="Net 45"></option>
        </datalist>
    </div>
    <div class="col-md-4">
        <label class="form-label">Lead Time (days)</label>
        <input type="number" min="0" max="365" step="1" name="lead_time_days" class="form-control" value="{{ old('lead_time_days', $supplier->lead_time_days ?? 7) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Minimum Order Amount (₱)</label>
        <input type="number" min="0" step="0.01" name="minimum_order_amount" class="form-control" value="{{ old('minimum_order_amount', $supplier->minimum_order_amount ?? 0) }}" required>
    </div>
</div>

<div class="form-check mb-4">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $supplier->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active">Active supplier (can be chosen on new purchase orders)</label>
</div>
