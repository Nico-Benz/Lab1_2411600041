<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Mark one notification as read, then go to the product. */
    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $data = $notification->data;

        if (! empty($data['url'])) {
            return redirect($data['url']);          // Lab 7: e.g. the draft purchase orders list
        }

        return ! empty($data['product_id'])
            ? redirect()->route('products.show', $data['product_id'])
            : back();
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}
