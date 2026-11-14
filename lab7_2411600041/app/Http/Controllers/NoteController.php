<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;

/** Polymorphic notes: the same notes table is used for suppliers and purchase orders */
class NoteController extends Controller
{
    public function store(Request $request)
    {
        abort_if($request->user()->role === 'viewer', 403);

        $data = $request->validate([
            'noteable_type' => 'required|in:supplier,purchase-order',
            'noteable_id' => 'required|integer',
            'body' => 'required|string|max:1000',
        ], [
            'body.required' => 'Please write the note.',
        ]);

        $model = $data['noteable_type'] === 'supplier'
            ? Supplier::findOrFail($data['noteable_id'])
            : PurchaseOrder::findOrFail($data['noteable_id']);

        $model->stickyNotes()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return back()->with('success', 'Note added.');
    }
}
