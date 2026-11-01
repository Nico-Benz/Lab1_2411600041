<?php

namespace App\Http\Controllers;

use App\Models\GradeItem;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $itemId = $notification->data['grade_item_id'] ?? null;

        if ($itemId && GradeItem::whereKey($itemId)->exists()) {
            return redirect()->route('grade-items.show', $itemId);
        }

        return back();
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}