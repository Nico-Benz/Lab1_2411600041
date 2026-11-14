<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

/**
 * Who can do what with a purchase order.
 *
 *            view  create  edit/submit  approve  send  receive  delete
 *  admin      yes   yes     yes          yes      yes   yes      yes (draft)
 *  manager    yes   yes     own draft    yes      yes   no       no
 *  staff      yes   yes     own draft    no       no    yes      no
 *  viewer     yes   no      no           no       no    no       no
 */
class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager', 'staff'], true);
    }

    /** Only DRAFT orders, only the creator or an admin */
    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->status === 'draft'
            && $this->create($user)
            && ($user->isAdmin() || (int) $purchaseOrder->created_by === (int) $user->id);
    }

    /** Only DRAFT orders, only an admin */
    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->status === 'draft' && $user->isAdmin();
    }

    /** Send a draft for approval */
    public function submit(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->status === 'draft'
            && $this->create($user)
            && ($user->isAdmin() || $user->isManager() || (int) $purchaseOrder->created_by === (int) $user->id);
    }

    /** Managers and admins only (also used for reject) */
    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return in_array($user->role, ['admin', 'manager'], true);
    }

    /** Mark as sent to the supplier */
    public function send(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return in_array($user->role, ['admin', 'manager'], true);
    }

    /** Warehouse staff and admins */
    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    public function cancel(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $purchaseOrder->canBeCancelled()
            && $this->create($user)
            && ($user->isAdmin() || $user->isManager() || (int) $purchaseOrder->created_by === (int) $user->id);
    }
}
