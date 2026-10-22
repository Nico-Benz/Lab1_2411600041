<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::with('account')->latest()->get();

        return view('transactions.index', compact('transactions'));
    }

    public function create()
    {
        $accounts = Account::all();

        return view('transactions.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        // 1. Basic validation
        $validator = Validator::make($request->all(), [
            'account_id'       => ['required', 'exists:accounts,id'],
            'type'             => ['required', 'in:deposit,withdrawal'],
            'amount'           => ['required', 'numeric', 'min:1'],
            'reference_number' => ['nullable', 'string'],
            'notes'            => ['nullable', 'string'],
        ]);

        // 2. Custom validation: sakto ba ang pera sa withdrawal?
        $validator->after(function ($validator) use ($request) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($request->type === 'withdrawal') {
                $account = Account::find($request->account_id);

                if ($account && ($account->balance - $request->amount) < $account->minimum_balance) {
                    $validator->errors()->add('amount', 'Insufficient funds. Balance cannot go below the minimum balance.');
                }
            }
        });

        $validated = $validator->validate();

        // 3. I-save sa loob ng DB::transaction()
        try {
            DB::transaction(function () use ($validated) {
                Transaction::create($validated);
            });
        } catch (Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction saved successfully.');
    }
}