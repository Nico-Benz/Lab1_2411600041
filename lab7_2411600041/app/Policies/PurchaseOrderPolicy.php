<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    /**
     * Lahat ng naka-login ay pwedeng tumingin ng listahan.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Lahat ng naka-login ay pwedeng tumingin ng isang PO.
     */
    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return true;
    }

    /**
     * Admin, manager at staff lang ang pwedeng gumawa. Hindi ang viewer.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager', 'staff']);
    }

    /**
     * Draft lang ang pwedeng i-edit, at ang gumawa o admin lang.
     */
    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->status === 'draft'
            && ($user->id === $purchaseOrder->created_by || $user->role === 'admin');
    }

    /**
     * Draft lang ang pwedeng i-cancel, at admin lang.
     */
    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->status === 'draft' && $user->role === 'admin';
    }

    /**
     * Manager at admin ang pwedeng mag-approve o mag-reject.
     */
    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Manager at admin ang pwedeng mag-mark ng "sent".
     */
    public function send(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Staff at admin ang pwedeng mag-receive.
     */
    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return in_array($user->role, ['admin', 'staff']);
    }
}