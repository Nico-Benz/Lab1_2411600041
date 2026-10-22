<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function stats()
    {
        return response()->json([
            'total_accounts'     => Account::count(),
            'total_money'        => (float) Account::sum('balance'),
            'transactions_today' => Transaction::whereDate('created_at', today())->count(),
        ]);
    }
}