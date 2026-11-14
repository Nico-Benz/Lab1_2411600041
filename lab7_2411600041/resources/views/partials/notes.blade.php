{{-- Polymorphic notes box. Needs $noteable (Supplier or PurchaseOrder) and $noteableType (supplier | purchase-order) --}}
@forelse ($noteable->stickyNotes as $note)
    <div class="border-start border-3 ps-3 mb-3" style="border-color: var(--theme-accent) !important;">
        <div>{{ $note->body }}</div>
        <div class="small text-muted">{{ $note->user->name ?? 'Unknown' }} · {{ $note->created_at->format('M d, Y g:i A') }}</div>
    </div>
@empty
    <p class="text-muted">No notes yet.</p>
@endforelse

@if (auth()->user()->role !== 'viewer')
    <form action="{{ route('notes.store') }}" method="POST" class="mt-3">
        @csrf
        <input type="hidden" name="noteable_type" value="{{ $noteableType }}">
        <input type="hidden" name="noteable_id" value="{{ $noteable->id }}">
        <div class="input-group">
            <input type="text" name="body" class="form-control" placeholder="Write a note..." maxlength="1000" required>
            <button type="submit" class="btn btn-dark">Add note</button>
        </div>
    </form>
@endif
