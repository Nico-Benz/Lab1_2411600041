@extends('layouts.grade-app')

@section('content')

@php
    $canReceive = $order->status === 'sent' && auth()->user()->can('receive', $order);
    $canApprove = $order->status === 'pending' && auth()->user()->can('approve', $order);
    $canSend    = $order->status === 'approved' && auth()->user()->can('send', $order);

    $badge = match ($order->status) {
        'draft'     => 'bg-secondary',
        'pending'   => 'bg-warning text-dark',
        'approved'  => 'bg-info text-dark',
        'rejected'  => 'bg-danger',
        'sent'      => 'bg-primary',
        'received'  => 'bg-success',
        'cancelled' => 'bg-dark',
        default     => 'bg-secondary',
    };
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center pb-2 mb-3 border-bottom gap-2">
    <h2 class="mb-0">
        {{ $order->reference_number }}
        <span class="badge {{ $badge }} fs-6 align-middle">{{ ucfirst($order->status) }}</span>
    </h2>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">Back</a>
        @if ($order->status === 'draft')
            @can('update', $order)
                <a href="{{ route('purchase-orders.edit', $order) }}" class="btn btn-outline-primary">Edit</a>
            @endcan
            @can('delete', $order)
                <form action="{{ route('purchase-orders.destroy', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this draft purchase order?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">Cancel PO</button>
                </form>
            @endcan
        @endif
    </div>
</div>

<!-- Header info -->
<div class="card stat-card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="text-muted small">Supplier (Instructor)</div>
                <div class="fw-semibold">
                    @if ($order->supplier && ! $order->supplier->trashed())
                        <a href="{{ route('suppliers.show', $order->supplier) }}">{{ $order->supplier->company_name }}</a>
                    @else
                        {{ $order->supplier->company_name ?? '—' }} @if ($order->supplier) (deleted) @endif
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Order Date</div>
                <div class="fw-semibold">{{ $order->order_date?->format('M d, Y') }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Expected Delivery</div>
                <div class="fw-semibold">{{ $order->expected_delivery_date?->format('M d, Y') ?? '—' }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small">Notes</div>
                <div>{{ $order->notes ?: '—' }}</div>
            </div>
            <div class="col-md-6">
                <div class="text-muted small">Terms</div>
                <div>{{ $order->terms ?: '—' }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Line items -->
<div class="card stat-card mb-3">
    <div class="card-body">
        <h5>Line Items</h5>

        @if ($canReceive)
            {{-- Walang laman na form. Ang mga input sa table ay kumakabit dito gamit ang form="receiveForm" --}}
            <form id="receiveForm" method="POST" action="{{ route('purchase-orders.receive', $order) }}">
                @csrf
            </form>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th class="text-end">Points to Recover</th>
                        <th class="text-end">Fee per Point</th>
                        <th class="text-end">Line Total</th>
                        <th class="text-end">Received</th>
                        <th class="text-end">Outstanding</th>
                        @if ($canReceive)
                            <th>Receive Now</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>{{ $item->product->subject_code ?? '—' }} - {{ $item->product->subject_name ?? '' }}</td>
                            <td class="text-end">{{ number_format($item->quantity_ordered, 2) }}</td>
                            <td class="text-end">₱{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end">₱{{ number_format($item->line_total, 2) }}</td>
                            <td class="text-end">{{ number_format($item->quantity_received, 2) }}</td>
                            <td class="text-end">{{ number_format($item->outstanding_quantity, 2) }}</td>
                            @if ($canReceive)
                                <td>
                                    <input type="number" step="0.01" min="0" max="{{ $item->outstanding_quantity }}"
                                           name="received[{{ $item->id }}]" form="receiveForm"
                                           class="form-control form-control-sm" style="max-width: 110px;">
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end">Subtotal</td>
                        <td class="text-end">₱{{ number_format($order->subtotal, 2) }}</td>
                        <td colspan="{{ $canReceive ? 3 : 2 }}"></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end">Tax</td>
                        <td class="text-end">₱{{ number_format($order->tax, 2) }}</td>
                        <td colspan="{{ $canReceive ? 3 : 2 }}"></td>
                    </tr>
                    <tr class="fw-bold">
                        <td colspan="3" class="text-end">Total</td>
                        <td class="text-end">₱{{ number_format($order->total, 2) }}</td>
                        <td colspan="{{ $canReceive ? 3 : 2 }}"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if ($canReceive)
            <button type="submit" form="receiveForm" class="btn" style="background-color: var(--theme-primary); color: white;">Record Received Items</button>
        @endif
    </div>
</div>

<!-- Approval history -->
<div class="card stat-card mb-3">
    <div class="card-body">
        <h5>Approval History</h5>
        <ul class="list-unstyled mb-0">
            <li>Created by <strong>{{ $order->createdBy->name ?? '—' }}</strong> on {{ $order->created_at?->format('M d, Y h:i A') }}</li>

            @if ($order->approvedBy)
                <li>
                    {{ $order->status === 'rejected' ? 'Reviewed by' : 'Approved by' }}
                    <strong>{{ $order->approvedBy->name }}</strong>
                    on {{ $order->approved_at?->format('M d, Y h:i A') }}
                </li>
            @endif

            @if ($order->status === 'rejected' && $order->rejection_reason)
                <li class="text-danger">Rejection reason: {{ $order->rejection_reason }}</li>
            @endif

            @if ($order->received_at)
                <li>Fully received on {{ $order->received_at->format('M d, Y h:i A') }}</li>
            @endif
        </ul>
    </div>
</div>

<!-- Actions by status -->
@if ($canApprove || $canSend)
    <div class="card stat-card mb-3">
        <div class="card-body">
            <h5>Actions</h5>

            @if ($canApprove)
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <form action="{{ route('purchase-orders.approve', $order) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success">Approve</button>
                    </form>
                </div>

                <form action="{{ route('purchase-orders.reject', $order) }}" method="POST">
                    @csrf
                    <label class="form-label">Reject with reason</label>
                    <textarea name="rejection_reason" rows="2" required class="form-control mb-2 @error('rejection_reason') is-invalid @enderror" placeholder="Why is this rejected?">{{ old('rejection_reason') }}</textarea>
                    @error('rejection_reason')
                        <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                    @enderror
                    <button type="submit" class="btn btn-danger">Reject</button>
                </form>
            @endif

            @if ($canSend)
                <form action="{{ route('purchase-orders.send', $order) }}" method="POST" onsubmit="return confirm('Mark this purchase order as sent to the instructor?');">
                    @csrf
                    <button type="submit" class="btn btn-primary">Mark as Sent</button>
                </form>
            @endif
        </div>
    </div>
@endif

@endsection