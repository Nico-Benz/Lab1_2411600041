# Lab 7 - Advanced MVC: Automated Inventory Replenishment and Supplier Management

Student Supplies Inventory (Laravel 12). Built on top of Lab 6 (real-time inventory tracking).

## What is new in Lab 7

| Part | Feature                                                                                                                                                                         | Where to look                                                                       |
| ---- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- |
| 1    | Suppliers (CRUD, soft delete, restore, search, sort, notes)                                                                                                                     | `SupplierController`, `app/Models/Supplier.php`, `resources/views/suppliers`        |
| 2    | Purchase orders (draft, pending, approved, rejected, sent, received, cancelled)                                                                                                 | `PurchaseOrderController`, `PurchaseOrderPolicy`, `resources/views/purchase-orders` |
| 3    | Automatic reorder: `inventory:check-reorder`, scheduled daily 6:00 AM (Asia/Manila)                                                                                             | `ReorderService`, `CheckReorderPoints`, `routes/console.php`                        |
| 4    | Queued emails: created (to admins and managers), approved (to the creator), sent (to the supplier), low stock. The created and sent emails have the purchase order PDF attached | `app/Mail`, `PurchaseOrderObserver`, `PdfService`, `resources/views/emails`         |
| 5    | Dashboard widgets (View Composers + AJAX refresh)                                                                                                                               | `ViewServiceProvider`, `ProcurementWidgets`, `resources/views/widgets`              |
| 6    | Supplier performance report with charts                                                                                                                                         | `SupplierPerformanceService`, `reports/supplier-performance`                        |
| 7    | Search and filters, reusable `<x-filter-form>` component                                                                                                                        | `SearchService`, `components/filter-form.blade.php`                                 |
| 8    | CSV and PDF exports                                                                                                                                                             | `ExportController`, `app/Exports`                                                   |
| 9    | Tests                                                                                                                                                                           | `tests/Feature/Lab7ProcurementTest.php`                                             |

Extra relationships: many-to-many (`product_supplier` with supplier price) and polymorphic notes (`notes` table for suppliers and purchase orders).

## Roles

| Role              | Can do                                                                              |
| ----------------- | ----------------------------------------------------------------------------------- |
| admin             | everything (suppliers CRUD, users, adjustments, delete draft orders, receive items) |
| manager           | create orders, submit, approve / reject, mark as sent, cancel                       |
| staff (warehouse) | create orders, edit / submit / cancel own draft, receive items                      |
| viewer            | read only                                                                           |

All seeded accounts use the password `password`:
`test@example.com` (admin), `manager@example.com`, `staff@example.com`, `viewer@example.com`.

## Formulas

```
Reorder point  = (average daily usage x supplier lead time) + safety stock
Average usage  = total "stock out" of the last 30 days / 30
Safety stock   = the product reorder level

Order quantity = target level minus current stock
  target level = (average usage x (lead time + 30 cover days)) + safety stock,
                 but never less than 2 x safety stock
  then: at least the supplier minimum order quantity, rounded up to the package size
```

Settings (tax rate, usage days, cover days, widget cache seconds) are in `config/procurement.php`.

## Setup

1. Start Apache and MySQL in XAMPP. In phpMyAdmin create the database `lab7_2411600041`.
2. In the project folder:
    ```
    composer install
    composer require barryvdh/laravel-dompdf
    copy .env.example .env        (or copy the .env of Lab 6 and change DB_DATABASE)
    php artisan key:generate
    php artisan migrate:fresh --seed
    ```
3. In `.env` use `MAIL_MAILER=log` so emails are written to `storage/logs/laravel.log` (the sample suppliers use fake email addresses).
4. Run (two terminals):
    ```
    php artisan serve
    php artisan queue:work
    ```
5. Try the automatic reorder: `php artisan inventory:check-reorder`
6. Show the schedule: `php artisan schedule:list` (run it for real with `php artisan schedule:work`)
7. Tests: `php artisan test`

## Quick demo flow

1. Login as admin, open the dashboard (see the procurement widgets).
2. Run `php artisan inventory:check-reorder`, then open Purchase Orders > filter Draft.
3. Open a draft > Send for Approval. Login as manager > Approve > Mark as Sent.
4. Login as staff > open the sent order > Receive items. Check Stock Movements and the product stock.
5. Open Reports > Supplier Performance, then export CSV / PDF from any list.
