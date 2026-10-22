<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transactions</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background: #f4f6f8; }
        h1 { color: #1f3a5f; }
        .btn { display: inline-block; padding: 8px 14px; background: #1f3a5f; color: #fff; text-decoration: none; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; padding: 10px; margin: 15px 0; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 15px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #1f3a5f; color: #fff; }
    </style>
</head>
<body>
    <h1>Simple Banking System</h1>
    
    <a class="btn" href="{{ route('transactions.create') }}">+ New Transaction</a>
    <a class="btn" href="{{ route('dashboard') }}">📊 Dashboard</a>

    @if (session('success'))
        <div class="success">{{ session('success') }}</div>
    @endif

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Account No.</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Reference</th>
                <th>Notes</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->id }}</td>
                    <td>{{ $transaction->account->account_number }}</td>
                    <td>{{ ucfirst($transaction->type) }}</td>
                    <td>{{ number_format($transaction->amount, 2) }}</td>
                    <td>{{ $transaction->reference_number }}</td>
                    <td>{{ $transaction->notes }}</td>
                    <td>{{ $transaction->created_at->format('M d, Y h:i A') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No transactions yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>