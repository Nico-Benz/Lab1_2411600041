@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>{{ $user->name }}</h2>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">← Back to Users</a>
</div>

<div class="card stat-card">
    <div class="card-body">
        <div class="row mb-2">
            <div class="col-3 text-muted">Name</div>
            <div class="col-9">{{ $user->name }}</div>
        </div>
        <div class="row mb-2">
            <div class="col-3 text-muted">Email Address</div>
            <div class="col-9">{{ $user->email }}</div>
        </div>
        <div class="row mb-2">
            <div class="col-3 text-muted">Date Registered</div>
            <div class="col-9">{{ $user->created_at->format('M d, Y g:i A') }}</div>
        </div>
        <div class="row">
            <div class="col-3 text-muted">Stock Movements Recorded</div>
            <div class="col-9">{{ $movementCount }}</div>
        </div>
    </div>
</div>

@endsection
