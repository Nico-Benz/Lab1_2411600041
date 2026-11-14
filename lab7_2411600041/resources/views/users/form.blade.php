@extends('layouts.inventory-app')

@section('content')

@php
    $editing = isset($user);
@endphp

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>{{ $editing ? 'Edit User' : 'Add New User' }}</h2>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">← Back to Users</a>
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

        <form action="{{ $editing ? route('users.update', $user) : route('users.store') }}" method="POST">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" {{ $editing ? '' : 'required' }}>
                    @if ($editing)
                        <div class="form-text">Leave blank to keep the current password.</div>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" {{ $editing ? '' : 'required' }}>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="viewer" {{ old('role', $user->role ?? 'viewer') === 'viewer' ? 'selected' : '' }}>Viewer</option>
                        <option value="staff" {{ old('role', $user->role ?? 'viewer') === 'staff' ? 'selected' : '' }}>Staff (warehouse)</option>
                        <option value="manager" {{ old('role', $user->role ?? 'viewer') === 'manager' ? 'selected' : '' }}>Manager</option>
                        <option value="admin" {{ old('role', $user->role ?? 'viewer') === 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                    <div class="form-text">Admins can edit products and users, adjust stock, and delete transactions.</div>
                </div>
            </div>

            <button type="submit" class="btn" style="background-color: var(--theme-primary); color: white;">
                {{ $editing ? 'Update User' : 'Save User' }}
            </button>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>

    </div>
</div>

@endsection
