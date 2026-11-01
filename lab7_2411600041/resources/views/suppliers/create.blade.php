@extends('layouts.grade-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Add Supplier</h2>
    <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card stat-card">
    <div class="card-body">
        <form method="POST" action="{{ route('suppliers.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Company Name</label>
                    <input type="text" name="company_name" value="{{ old('company_name') }}" class="form-control @error('company_name') is-invalid @enderror">
                    @error('company_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="contact_person" value="{{ old('contact_person') }}" class="form-control @error('contact_person') is-invalid @enderror">
                    @error('contact_person')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" placeholder="e.g. 0917 123 4567">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Payment Terms</label>
                    <select name="payment_terms" class="form-select @error('payment_terms') is-invalid @enderror">
                        @foreach (['Net 15', 'Net 30', 'Net 60', 'COD'] as $term)
                            <option value="{{ $term }}" @selected(old('payment_terms', 'Net 30') === $term)>{{ $term }}</option>
                        @endforeach
                    </select>
                    @error('payment_terms')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Lead Time (days)</label>
                    <input type="number" name="lead_time_days" value="{{ old('lead_time_days', 7) }}" min="0" max="365" class="form-control @error('lead_time_days') is-invalid @enderror">
                    @error('lead_time_days')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Minimum Order Amount</label>
                    <input type="number" step="0.01" min="0" name="minimum_order_amount" value="{{ old('minimum_order_amount', 0) }}" class="form-control @error('minimum_order_amount') is-invalid @enderror">
                    @error('minimum_order_amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked($errors->any() ? old('is_active') : true)>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn" style="background-color: var(--theme-primary); color: white;">Save Supplier</button>
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection