@extends('layouts.inventory-app')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom flex-wrap gap-2">
    <h2 class="mb-0">
        {{ $order->reference_number }}
        <span class="badge bg-{{ $order->status_color }} fs-6">{{ $order->status_label }}</span>
        @if ($order->is_auto_generated)
            <span class="badge bg-light text-dark border fs-6">Auto-generated</span>
        @endif
    </h2>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">← Back</a>
        <a href="{{ route('purchase-orders.pdf', $order) }}" class="btn btn-outline-secondary">PDF</a>

        @can('update', $order)
            <a href="{{ route('purchase-orders.edit', $order) }}" class="btn btn-outline-primary">Edit</a>
        @endcan

        @can('submit', $order)
            <form action="{{ route('purchase-orders.submit', $order) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning">Send for Approval</button>
            </form>
        @endcan

        @can('approve', $order)
            @if ($order->canBeApproved())
                <form action="{{ route('purchase-orders.approve', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Approve this purchase order?');">
                    @csrf
                    <button type="submit" class="btn btn-success">Approve</button>
                </form>
                <button type="button" class="btn btn-danger" data-bs-toggle="collapse" data-bs-target="#rejectBox">Reject</button>
            @endif
        @endcan

        @can('send', $order)
            @if ($order->canBeSent())
                <form action="{{ route('purchase-orders.send', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Mark as sent? The supplier will get an email.');">
                    @csrf
                    <button type="submit" class="btn btn-info text-white">Mark as Sent</button>
                </form>
            @endif
        @endcan

        @can('cancel', $order)
            <form action="{{ route('purchase-orders.cancel', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this purchase order?');">
                @csrf
                <button type="submit" class="btn btn-outline-dark">Cancel Order</button>
            </form>
        @endcan

        @can('delete', $order)
            <form action="{{ route('purchase-orders.destroy', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this draft purchase order?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Delete</button>
            </form>
        @endcan
    </div>
</div>

@can('approve', $order)
    @if ($order->canBeApproved())
        <div class="collapse mb-3 {{ $errors->has('rejection_reason') ? 'show' : '' }}" id="rejectBox">
            <div class="card border-danger">
                <div class="card-body">
                    <form action="{{ route('purchase-orders.reject', $order) }}" method="POST">
                        @csrf
                        <label class="form-label">Reason for rejecting</label>
                        <textarea name="rejection_reason" class="form-control mb-2 @error('rejection_reason') is-invalid @enderror" rows="2" required>{{ old('rejection_reason') }}</textarea>
                        @error('rejection_reason')
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror
                        <button type="submit" class="btn btn-danger">Confirm Reject</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endcan

@if ($order->status === 'rejected' && $order->rejection_reason)
    <div class="alert alert-danger"><strong>Rejected:</strong> {{ $order->rejection_reason }}</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h5 class="card-title">Order information</h5>
                <div class="row mb-2">
                    <div class="col-5 text-muted">Supplier</div>
                    <div class="col-7">
                        @if ($order->supplier && ! $order->supplier->trashed())
                            <a href="{{ route('suppliers.show', $order->supplier) }}">{{ $order->supplier->name }}</a>
                        @else
                            {{ $order->supplier->name ?? '—' }} <span class="badge bg-dark">deleted</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-2"><div class="col-5 text-muted">Contact</div><div class="col-7">{{ $order->supplier->full_contact ?? '—' }}</div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Payment terms</div><div class="col-7">{{ $order->supplier->payment_terms ?? '—' }}</div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Order date</div><div class="col-7">{{ $order->order_date->format('M d, Y') }}</div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Expected delivery</div><div class="col-7">{{ $order->expected_delivery_date ? $order->expected_delivery_date->format('M d, Y') : '—' }}</div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Created by</div><div class="col-7">{{ $order->createdBy->name ?? '—' }}</div></div>
                <div class="row mb-2">
                    <div class="col-5 text-muted">Approved by</div>
                    <div class="col-7">{{ $order->approvedBy->name ?? '—' }} @if ($order->approved_at)<span class="text-muted small">({{ $order->approved_at->format('M d, Y g:i A') }})</span>@endif</div>
                </div>
                <div class="row mb-2"><div class="col-5 text-muted">Sent</div><div class="col-7">{{ $order->sent_at ? $order->sent_at->format('M d, Y g:i A') : '—' }}</div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Fully received</div><div class="col-7">{{ $order->received_at ? $order->received_at->format('M d, Y g:i A') : '—' }}</div></div>
                @if ($order->notes)
                    <div class="row mb-2"><div class="col-5 text-muted">Notes</div><div class="col-7">{{ $order->notes }}</div></div>
                @endif
                @if ($order->terms)
                    <div class="row mb-2"><div class="col-5 text-muted">Terms</div><div class="col-7">{{ $order->terms }}</div></div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <h5 class="card-title">Approval history</h5>
                <ul class="list-unstyled mb-0">
                    @forelse ($order->statusLogs as $log)
                        <li class="mb-2 pb-2 border-bottom">
                            <strong>{{ $log->action_label }}</strong>
                            <span class="text-muted small">by {{ $log->user->name ?? 'System' }} · {{ $log->created_at->format('M d, Y g:i A') }}</span>
                            @if ($log->comment)
                                <div class="small">{{ $log->comment }}</div>
                            @endif
                        </li>
                    @empty
                        <li class="text-muted">No history yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="card stat-card mb-3">
    <div class="card-body">
        <h5 class="card-title">Line items</h5>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Unit price</th>
                        <th class="text-end">Line total</th>
                        <th class="text-end">Received</th>
                        <th class="text-end">Outstanding</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>{{ $item->product->sku ?? '—' }}</td>
                            <td>@if ($item->product)<a href="{{ route('products.show', $item->product) }}">{{ $item->product->name }}</a>@else — @endif</td>
                            <td class="text-end">{{ $item->quantity }}</td>
                            <td class="text-end">₱{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="text-end">₱{{ number_format($item->line_total, 2) }}</td>
                            <td class="text-end">{{ $item->received_quantity }}</td>
                            <td class="text-end">{{ $item->outstanding_quantity }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><td colspan="4" class="text-end">Subtotal</td><td class="text-end">₱{{ number_format((float) $order->subtotal, 2) }}</td><td colspan="2"></td></tr>
                    <tr><td colspan="4" class="text-end">Tax ({{ rtrim(rtrim(number_format(config('procurement.tax_rate') * 100, 2), '0'), '.') }}%)</td><td class="text-end">₱{{ number_format((float) $order->tax, 2) }}</td><td colspan="2"></td></tr>
                    <tr class="fw-bold"><td colspan="4" class="text-end">Total</td><td class="text-end">₱{{ number_format((float) $order->total, 2) }}</td><td colspan="2"></td></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@can('receive', $order)
    @if ($order->canBeReceived())
        <div class="card stat-card mb-3 border-success">
            <div class="card-body">
                <h5 class="card-title">Receive items</h5>
                <p class="text-muted small">Type how many pieces arrived. Each one adds a Stock In movement and increases the product stock. You can receive in parts.</p>

                @error('received')
                    <div class="alert alert-danger">{{ $message }}</div>
                @enderror

                <form action="{{ route('purchase-orders.receive', $order) }}" method="POST">
                    @csrf
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>Product</th><th class="text-end">Outstanding</th><th style="width: 160px;">Received now</th></tr></thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    @if ($item->outstanding_quantity > 0)
                                        <tr>
                                            <td>{{ $item->product->name }}</td>
                                            <td class="text-end">{{ $item->outstanding_quantity }}</td>
                                            <td>
                                                <input type="number" min="0" max="{{ $item->outstanding_quantity }}" name="received[{{ $item->id }}]"
                                                       value="{{ old("received.{$item->id}", $item->outstanding_quantity) }}"
                                                       class="form-control @error("received.{$item->id}") is-invalid @enderror">
                                                @error("received.{$item->id}")
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-success">Confirm Receipt</button>
                </form>
            </div>
        </div>
    @endif
@endcan

<div class="card stat-card">
    <div class="card-body">
        <h5 class="card-title">Notes</h5>
        @include('partials.notes', ['noteable' => $order, 'noteableType' => 'purchase-order'])
    </div>
</div>

@endsection
