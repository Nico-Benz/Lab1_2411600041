# Student Grade Portal - Lab 7

Advanced MVC Architecture with Laravel: Automated Inventory Replenishment and Supplier Management System.

Built by Nico Benz A. Sasing (2411600041) for Web Systems and Technologies. This is the continuation of the Student Grade Portal from Lab 5 and Lab 6.

## How the Lab 7 words map to this project

| Lab 7 term     | In this project                                                                     |
| -------------- | ----------------------------------------------------------------------------------- |
| Product        | Grade item (a subject)                                                              |
| Supplier       | Instructor                                                                          |
| Purchase Order | Remedial request sent to the instructor for an At Risk subject                      |
| Order quantity | Points the grade needs to recover                                                   |
| Unit price     | Fee per point (default 50)                                                          |
| Reorder point  | Grade level that triggers an automatic request                                      |
| Receive items  | Instructor completes the remedial work, and a grade increase transaction is created |

## New features in Lab 7

- **Supplier module:** create, view, edit and soft delete suppliers. Deleted suppliers are hidden from lists but kept in the database, and can be restored.
- **Purchase Order system:** PO items, auto reference numbers (PO-YYYYMMDD-0001), statuses (draft, pending, approved, rejected, sent, received, cancelled), approval history, and a PurchaseOrderPolicy for role-based rules.
- **Automated reorder:** ReorderService computes the reorder point from recent usage and creates purchase orders. Command: `php artisan inventory:check-reorder`. It is scheduled daily at 6:00 AM (Asia/Manila) in `routes/console.php`.
- **Email notifications:** four Mailables (created, approved, sent, low stock alert) with email templates. The PO PDF is attached to the created email.
- **Procurement dashboard:** widgets fed by View Composers, API endpoints, and 60-second query caching.
- **Supplier performance:** SupplierPerformanceService and a report page with charts (`/reports/suppliers`).
- **Search and filtering:** scopeFilter on models, SearchService, and a reusable filter form component.
- **Exports:** CSV and PDF (barryvdh/laravel-dompdf) through ExportService and ExportController.
- **Advanced Eloquent relationships:** many-to-many (`grade_item_supplier` pivot table) and polymorphic (`notes` table used by Supplier and PurchaseOrder).

## Roles

admin, manager, staff and viewer. The `admin` Gate is in `AppServiceProvider`. Purchase order rules are in `app/Policies/PurchaseOrderPolicy.php`.

## How to run

1. Install PHP 8.2+, Composer, and XAMPP (start MySQL).
2. Copy `.env.example` to `.env` and set `DB_DATABASE=lab7_2411600041`.
3. Create the database `lab7_2411600041` in phpMyAdmin.
4. Run these commands:

```
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8002
php artisan queue:work
```

5. Open http://127.0.0.1:8002

## Notes

- Emails use `MAIL_MAILER=log`, so they are written to `storage/logs/laravel.log`.
- Queue driver is `database`, so `php artisan queue:work` must be running to send emails and bell notifications.
- Supplier performance measures delivered quantities (received vs ordered), not item accuracy.
- The Subjects page keeps its own JavaScript filter. The server-side filter form is used on the Purchase Orders and Transactions pages.
- The `.env` file is not committed.
- `php artisan migrate --seed` creates the Test Admin account and the subjects only. Suppliers, purchase orders and the other test users are not seeded, so they must be added in the app.
