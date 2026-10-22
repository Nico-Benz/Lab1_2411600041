<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Banking Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 30px; }
        h1 { text-align: center; color: #333; }
        .cards { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; margin-top: 30px; }
        .card { background: #fff; padding: 25px 35px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; min-width: 220px; }
        .card h3 { margin: 0 0 10px; color: #666; font-size: 16px; }
        .card .value { font-size: 32px; font-weight: bold; color: #2c7be5; }
        .status { text-align: center; margin-top: 25px; color: #888; font-size: 14px; }
        .links { text-align: center; margin-top: 20px; }
        .links a { color: #2c7be5; text-decoration: none; }
    </style>
</head>
<body>
    <h1>🏦 Banking Dashboard</h1>

    <div class="cards">
        <div class="card">
            <h3>Total Accounts</h3>
            <div class="value" id="total-accounts">...</div>
        </div>
        <div class="card">
            <h3>Total Money in Bank</h3>
            <div class="value" id="total-money">...</div>
        </div>
        <div class="card">
            <h3>Transactions Today</h3>
            <div class="value" id="transactions-today">...</div>
        </div>
    </div>

    <div class="status" id="status">Loading...</div>

    <div class="links">
        <a href="{{ route('transactions.index') }}">← Back to Transactions</a>
    </div>

    <script>
        function loadStats() {
            fetch('/api/dashboard/stats')
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    document.getElementById('total-accounts').textContent = data.total_accounts;
                    document.getElementById('total-money').textContent =
                        '₱' + Number(data.total_money).toLocaleString('en-PH', { minimumFractionDigits: 2 });
                    document.getElementById('transactions-today').textContent = data.transactions_today;
                    document.getElementById('status').textContent =
                        'Last updated: ' + new Date().toLocaleTimeString();
                })
                .catch(function (error) {
                    document.getElementById('status').textContent = 'Error loading stats';
                    console.error(error);
                });
        }

        loadStats();                    // isang load agad pagbukas ng page
        setInterval(loadStats, 30000);  // mag ulit every 30,000 ms = 30 segundo
    </script>
</body>
</html>