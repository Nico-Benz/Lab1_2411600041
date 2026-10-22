<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Transaction</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background: #f4f6f8; }
        h1 { color: #1f3a5f; }
        .card { background: #fff; padding: 20px; max-width: 450px; border-radius: 6px; }
        label { display: block; margin-top: 12px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; }
        .btn { margin-top: 15px; padding: 8px 14px; background: #1f3a5f; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .back { margin-left: 10px; color: #1f3a5f; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>New Transaction</h1>

    <div class="card">
        @if ($errors->any())
            <div class="error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('transactions.store') }}">
            @csrf

            <label for="account_id">Account</label>
            <select name="account_id" id="account_id">
                <option value="">-- Select account --</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>
                        {{ $account->account_number }} (Balance: {{ number_format($account->balance, 2) }})
                    </option>
                @endforeach
            </select>

            <label for="type">Type</label>
            <select name="type" id="type">
                <option value="deposit" {{ old('type') === 'deposit' ? 'selected' : '' }}>Deposit</option>
                <option value="withdrawal" {{ old('type') === 'withdrawal' ? 'selected' : '' }}>Withdrawal</option>
            </select>

            <label for="amount">Amount</label>
            <input type="number" step="0.01" name="amount" id="amount" value="{{ old('amount') }}">

            <label for="reference_number">Reference Number (optional)</label>
            <input type="text" name="reference_number" id="reference_number" value="{{ old('reference_number') }}">

            <label for="notes">Notes (optional)</label>
            <textarea name="notes" id="notes" rows="3">{{ old('notes') }}</textarea>

            <button type="submit" class="btn">Submit</button>
            <a class="back" href="{{ route('transactions.index') }}">Cancel</a>
        </form>
    </div>
</body>
</html>