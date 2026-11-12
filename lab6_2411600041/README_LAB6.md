# Lab 6 - Real-Time Inventory Tracking (Student Supplies Inventory)

Built on Lab 5 (login, register, forgot password, products, brands, categories, users, stock movements).

New in Lab 6: InventoryTransaction observer (auto stock update + negative stock check), DB transactions,
LowStockNotification (database + mail, queued), notification bell, API endpoints, AJAX polling dashboard (30 s),
stock adjustments, transaction history with running balance, reports (CSV export), admin/viewer roles.

## Run
1. Start Apache and MySQL in XAMPP. In phpMyAdmin create the database `lab6_inventory_2411600041`.
2. `php artisan migrate:fresh --seed`
3. Terminal 1: `php artisan serve`   Terminal 2: `php artisan queue:work`
4. Login: test@example.com / password (admin)

Tests: `php artisan test`
