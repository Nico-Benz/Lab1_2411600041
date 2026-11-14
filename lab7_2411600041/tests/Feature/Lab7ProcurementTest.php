<?php

namespace Tests\Feature;

use App\Mail\LowStockAlert;
use App\Mail\PurchaseOrderApproved;
use App\Mail\PurchaseOrderCreated;
use App\Mail\PurchaseOrderSent;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ReorderService;
use App\Services\SupplierPerformanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class Lab7ProcurementTest extends TestCase
{
    use RefreshDatabase;

    // ---------- helpers ----------

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function supplier(array $overrides = []): Supplier
    {
        return Supplier::create(array_merge([
            'name' => 'Test Supplier ' . uniqid(),
            'contact_person' => 'Juan Dela Cruz',
            'email' => uniqid('sup') . '@example.com',
            'phone' => '0917 123 4567',
            'payment_terms' => 'Net 30',
            'lead_time_days' => 7,
            'minimum_order_amount' => 0,
            'is_active' => true,
        ], $overrides));
    }

    private function product(Supplier $supplier, array $overrides = []): Product
    {
        $category = Category::firstOrCreate(['name' => 'Paper Products'], ['description' => 'x']);

        return Product::create(array_merge([
            'name' => 'Notebook ' . uniqid(),
            'sku' => 'T-' . uniqid(),
            'category_id' => $category->id,
            'quantity' => 50,
            'reorder_level' => 10,
            'unit_price' => 100,
            'supplier_id' => $supplier->id,
        ], $overrides));
    }

    /** Create a purchase order with items: [[Product, qty], ...] */
    private function order(Supplier $supplier, User $creator, string $status, array $lines, array $overrides = []): PurchaseOrder
    {
        $order = PurchaseOrder::create(array_merge([
            'reference_number' => PurchaseOrder::generateReferenceNumber(),
            'supplier_id' => $supplier->id,
            'status' => $status,
            'order_date' => today(),
            'expected_delivery_date' => today()->addDays(7),
            'created_by' => $creator->id,
        ], $overrides));

        foreach ($lines as [$product, $quantity]) {
            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => 100,
            ]);
        }

        return $order->calculateTotal();
    }

    // ---------- Part 1: suppliers ----------

    public function test_admin_can_create_update_and_soft_delete_a_supplier(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->post(route('suppliers.store'), [
            'name' => 'Rizal Paper Co',
            'contact_person' => 'Ana Reyes',
            'email' => 'ana@rizalpaper.example.com',
            'phone' => '0917 111 2222',
            'payment_terms' => 'Net 15',
            'lead_time_days' => 5,
            'minimum_order_amount' => 500,
            'is_active' => 1,
        ])->assertRedirect();

        $supplier = Supplier::where('email', 'ana@rizalpaper.example.com')->firstOrFail();

        $this->actingAs($admin)->put(route('suppliers.update', $supplier), [
            'name' => 'Rizal Paper Company',
            'contact_person' => 'Ana Reyes',
            'email' => 'ana@rizalpaper.example.com',
            'phone' => '0917 111 2222',
            'payment_terms' => 'Net 15',
            'lead_time_days' => 6,
            'minimum_order_amount' => 500,
            'is_active' => 1,
        ])->assertRedirect(route('suppliers.show', $supplier));

        $this->assertSame('Rizal Paper Company', $supplier->fresh()->name);
        $this->assertSame(6, $supplier->fresh()->lead_time_days);

        $this->actingAs($admin)->delete(route('suppliers.destroy', $supplier))->assertRedirect(route('suppliers.index'));
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);

        $this->actingAs($admin)->post(route('suppliers.restore', $supplier->id))->assertRedirect(route('suppliers.index'));
        $this->assertNull($supplier->fresh()->deleted_at);
    }

    public function test_supplier_validation_rejects_duplicate_email_and_bad_phone(): void
    {
        $admin = $this->user('admin');
        $existing = $this->supplier(['email' => 'same@example.com']);

        $this->actingAs($admin)->post(route('suppliers.store'), [
            'name' => 'Another', 'contact_person' => 'Someone', 'email' => 'same@example.com',
            'phone' => 'abc', 'payment_terms' => 'Net 30', 'lead_time_days' => 5, 'minimum_order_amount' => 0,
        ])->assertSessionHasErrors(['email', 'phone']);

        $this->assertSame(1, Supplier::where('email', 'same@example.com')->count());
        $this->assertNotNull($existing->id);
    }

    public function test_supplier_with_open_orders_cannot_be_deleted(): void
    {
        $admin = $this->user('admin');
        $supplier = $this->supplier();
        $product = $this->product($supplier);
        $this->order($supplier, $admin, 'pending', [[$product, 5]]);

        $this->actingAs($admin)->delete(route('suppliers.destroy', $supplier))->assertSessionHas('error');
        $this->assertNull($supplier->fresh()->deleted_at);
    }

    public function test_only_admin_can_manage_suppliers_but_everyone_can_view(): void
    {
        $supplier = $this->supplier();

        $this->actingAs($this->user('viewer'))->get(route('suppliers.index'))->assertOk();
        $this->actingAs($this->user('viewer'))->get(route('suppliers.show', $supplier))->assertOk();
        $this->actingAs($this->user('manager'))->get(route('suppliers.create'))->assertForbidden();
        $this->actingAs($this->user('staff'))->delete(route('suppliers.destroy', $supplier))->assertForbidden();
    }

    public function test_deleted_supplier_still_shows_on_old_orders(): void
    {
        $admin = $this->user('admin');
        $supplier = $this->supplier(['name' => 'Gone Trading']);
        $product = $this->product($supplier);
        $order = $this->order($supplier, $admin, 'received', [[$product, 5]]);

        $supplier->delete();

        $this->assertSame('Gone Trading', $order->fresh()->supplier->name);
        $this->actingAs($admin)->get(route('purchase-orders.show', $order))->assertOk()->assertSee('Gone Trading');
    }

    // ---------- Part 3: reorder service and command ----------

    public function test_reorder_point_and_optimal_quantity_are_calculated(): void
    {
        $admin = $this->user('admin');
        $supplier = $this->supplier(['lead_time_days' => 7]);
        $product = $this->product($supplier, ['quantity' => 100, 'reorder_level' => 10, 'package_size' => 5, 'min_order_quantity' => 10]);

        // 30 pieces out in the last 30 days = 1 piece per day. Stock: 100 -> 70
        InventoryTransaction::create([
            'product_id' => $product->id, 'transaction_type' => 'out', 'quantity' => 30,
            'reference_number' => 'TEST', 'user_id' => $admin->id,
        ]);
        $product->refresh();

        $service = new ReorderService();

        $this->assertEquals(1.0, $service->averageDailyUsage($product));
        $this->assertSame(17, $service->calculateReorderPoint($product));   // 1 x 7 + 10
        $this->assertCount(0, $service->getProductsNeedingReorder());      // 70 is far above 17

        $product->update(['quantity' => 10]);
        $service = new ReorderService();

        $this->assertCount(1, $service->getProductsNeedingReorder());
        // target = ceil(1 x (7 + 30)) + 10 = 47 -> need 47 - 10 = 37 -> rounded up to packages of 5 = 40
        $this->assertSame(40, $service->calculateOptimalOrderQuantity($product->fresh('supplier')));
    }

    public function test_command_creates_one_draft_order_per_supplier_and_does_not_duplicate(): void
    {
        Mail::fake();
        $admin = $this->user('admin');
        $supplier = $this->supplier();
        $this->product($supplier, ['quantity' => 2, 'reorder_level' => 10]);
        $this->product($supplier, ['quantity' => 3, 'reorder_level' => 10]);
        $this->product($supplier, ['quantity' => 500, 'reorder_level' => 10]); // plenty of stock

        $this->artisan('inventory:check-reorder')->assertExitCode(0);

        $this->assertSame(1, PurchaseOrder::count());
        $order = PurchaseOrder::with('items')->first();
        $this->assertSame('draft', $order->status);
        $this->assertTrue($order->is_auto_generated);
        $this->assertCount(2, $order->items);
        $this->assertSame($admin->id, $order->created_by);
        $this->assertGreaterThan(0, (float) $order->total);
        $this->assertSame(1, $admin->notifications()->count());

        // Running it again must not create a second order for the same products
        $this->artisan('inventory:check-reorder')->assertExitCode(0);
        $this->assertSame(1, PurchaseOrder::count());
    }

    public function test_products_without_active_supplier_are_skipped(): void
    {
        Mail::fake();
        $this->user('admin');
        $inactive = $this->supplier(['is_active' => false]);
        $this->product($inactive, ['quantity' => 0, 'reorder_level' => 10]);

        $this->artisan('inventory:check-reorder')->assertExitCode(0);

        $this->assertSame(0, PurchaseOrder::count());
    }

    // ---------- Part 2 and 4: purchase order workflow, policy, emails ----------

    public function test_staff_can_create_a_purchase_order_and_viewer_cannot(): void
    {
        Mail::fake();
        $admin = $this->user('admin');
        $this->user('manager');
        $staff = $this->user('staff');
        $supplier = $this->supplier();
        $product = $this->product($supplier);

        $payload = [
            'supplier_id' => $supplier->id,
            'order_date' => today()->format('Y-m-d'),
            'expected_delivery_date' => today()->addDays(7)->format('Y-m-d'),
            'items' => [['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 80]],
            'action' => 'submit',
        ];

        $this->actingAs($this->user('viewer'))->post(route('purchase-orders.store'), $payload)->assertForbidden();

        $this->actingAs($staff)->post(route('purchase-orders.store'), $payload)->assertRedirect();

        $order = PurchaseOrder::firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertSame(800.0, (float) $order->subtotal);
        $this->assertSame(96.0, (float) $order->tax);        // 12%
        $this->assertSame(896.0, (float) $order->total);
        $this->assertMatchesRegularExpression('/^PO-\d{8}-0001$/', $order->reference_number);

        Mail::assertQueued(PurchaseOrderCreated::class);
        $this->assertNotNull($admin->id);
    }

    public function test_purchase_order_validation_blocks_empty_and_duplicate_lines(): void
    {
        Mail::fake();
        $staff = $this->user('staff');
        $supplier = $this->supplier();
        $product = $this->product($supplier);

        $this->actingAs($staff)->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id, 'order_date' => today()->format('Y-m-d'), 'items' => [],
        ])->assertSessionHasErrors('items');

        $this->actingAs($staff)->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id, 'order_date' => today()->format('Y-m-d'),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10],
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 10],
            ],
        ])->assertSessionHasErrors('items.0.product_id');

        $this->assertSame(0, PurchaseOrder::count());
    }

    public function test_manager_approves_and_sends_but_staff_cannot(): void
    {
        Mail::fake();
        $manager = $this->user('manager');
        $staff = $this->user('staff');
        $supplier = $this->supplier();
        $product = $this->product($supplier);
        $order = $this->order($supplier, $staff, 'pending', [[$product, 5]]);

        $this->actingAs($staff)->post(route('purchase-orders.approve', $order))->assertForbidden();
        $this->actingAs($this->user('viewer'))->post(route('purchase-orders.approve', $order))->assertForbidden();

        $this->actingAs($manager)->post(route('purchase-orders.approve', $order))->assertSessionHas('success');
        $order->refresh();
        $this->assertSame('approved', $order->status);
        $this->assertSame($manager->id, $order->approved_by);
        $this->assertNotNull($order->approved_at);
        Mail::assertQueued(PurchaseOrderApproved::class);

        // approving again is refused with a message, not an error page
        $this->actingAs($manager)->post(route('purchase-orders.approve', $order))->assertSessionHas('error');

        $this->actingAs($staff)->post(route('purchase-orders.send', $order))->assertForbidden();
        $this->actingAs($manager)->post(route('purchase-orders.send', $order))->assertSessionHas('success');
        $this->assertSame('sent', $order->fresh()->status);
        Mail::assertQueued(PurchaseOrderSent::class);

        // every step is written in the approval history
        $this->assertGreaterThanOrEqual(3, $order->statusLogs()->count());
    }

    public function test_manager_can_reject_with_a_reason(): void
    {
        Mail::fake();
        $manager = $this->user('manager');
        $supplier = $this->supplier();
        $product = $this->product($supplier);
        $order = $this->order($supplier, $this->user('staff'), 'pending', [[$product, 5]]);

        $this->actingAs($manager)->post(route('purchase-orders.reject', $order), [])->assertSessionHasErrors('rejection_reason');

        $this->actingAs($manager)->post(route('purchase-orders.reject', $order), ['rejection_reason' => 'Too expensive'])
            ->assertSessionHas('success');

        $this->assertSame('rejected', $order->fresh()->status);
        $this->assertSame('Too expensive', $order->fresh()->rejection_reason);
    }

    public function test_only_draft_orders_can_be_edited_or_deleted(): void
    {
        Mail::fake();
        $admin = $this->user('admin');
        $supplier = $this->supplier();
        $product = $this->product($supplier);
        $draft = $this->order($supplier, $admin, 'draft', [[$product, 5]]);
        $sent = $this->order($supplier, $admin, 'sent', [[$product, 5]]);

        $this->actingAs($admin)->get(route('purchase-orders.edit', $draft))->assertOk();
        $this->actingAs($admin)->get(route('purchase-orders.edit', $sent))->assertForbidden();
        $this->actingAs($admin)->delete(route('purchase-orders.destroy', $sent))->assertForbidden();

        $this->actingAs($admin)->delete(route('purchase-orders.destroy', $draft))->assertRedirect(route('purchase-orders.index'));
        $this->assertSoftDeleted('purchase_orders', ['id' => $draft->id]);
    }

    public function test_receiving_adds_stock_and_completes_the_order(): void
    {
        Mail::fake();
        $staff = $this->user('staff');
        $supplier = $this->supplier();
        $product = $this->product($supplier, ['quantity' => 5]);
        $order = $this->order($supplier, $staff, 'sent', [[$product, 20]]);
        $item = $order->items()->first();

        // only staff and admin may receive
        $this->actingAs($this->user('manager'))->post(route('purchase-orders.receive', $order), ['received' => [$item->id => 5]])->assertForbidden();

        // more than outstanding is refused
        $this->actingAs($staff)->post(route('purchase-orders.receive', $order), ['received' => [$item->id => 25]])
            ->assertSessionHasErrors("received.{$item->id}");
        $this->assertSame(5, $product->fresh()->quantity);

        // partial receipt
        $this->actingAs($staff)->post(route('purchase-orders.receive', $order), ['received' => [$item->id => 12]])->assertSessionHas('success');
        $this->assertSame(17, $product->fresh()->quantity);
        $this->assertSame('sent', $order->fresh()->status);
        $this->assertSame(12, $item->fresh()->received_quantity);

        // the rest
        $this->actingAs($staff)->post(route('purchase-orders.receive', $order), ['received' => [$item->id => 8]])->assertSessionHas('success');
        $this->assertSame(25, $product->fresh()->quantity);
        $this->assertSame('received', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->received_at);

        $this->assertSame(2, InventoryTransaction::where('reference_number', $order->reference_number)->where('transaction_type', 'in')->count());
    }

    public function test_low_stock_sends_the_alert_email_to_admins(): void
    {
        Mail::fake();
        $admin = $this->user('admin');
        $supplier = $this->supplier();
        $product = $this->product($supplier, ['quantity' => 20, 'reorder_level' => 10]);

        InventoryTransaction::create([
            'product_id' => $product->id, 'transaction_type' => 'out', 'quantity' => 15,
            'reference_number' => 'TEST', 'user_id' => $admin->id,
        ]);

        Mail::assertQueued(LowStockAlert::class);
        $this->assertSame(1, $admin->notifications()->count());
    }

    // ---------- Part 5: dashboard widgets ----------

    public function test_dashboard_shows_the_procurement_widgets(): void
    {
        Mail::fake();
        $admin = $this->user('admin');
        $supplier = $this->supplier(['name' => 'Widget Supplier']);
        $low = $this->product($supplier, ['name' => 'Almost Gone Pen', 'quantity' => 1, 'reorder_level' => 10]);
        $this->order($supplier, $admin, 'pending', [[$low, 5]]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pending Approval')
            ->assertSee('Reorder Alerts')
            ->assertSee('Recent Purchase Orders')
            ->assertSee('Top Suppliers')
            ->assertSee('Almost Gone Pen');
    }

    public function test_procurement_api_endpoints_return_json(): void
    {
        Mail::fake();
        $admin = $this->user('admin');
        $supplier = $this->supplier();
        $product = $this->product($supplier, ['quantity' => 0]);
        $this->order($supplier, $admin, 'pending', [[$product, 5]]);

        $this->actingAs($admin)->getJson(route('api.pendingOrders'))
            ->assertOk()->assertJsonStructure(['count', 'value', 'orders' => [['reference_number', 'supplier', 'status', 'total', 'url']]])
            ->assertJsonPath('count', 1);

        $this->actingAs($admin)->getJson(route('api.recentOrders'))->assertOk()->assertJsonStructure(['orders']);
        $this->actingAs($admin)->getJson(route('api.supplierPerformance'))->assertOk()->assertJsonStructure(['suppliers']);
        $this->actingAs($admin)->getJson(route('api.reorderAlerts'))->assertOk()->assertJsonStructure(['products']);
    }

    // ---------- Part 6: supplier performance ----------

    public function test_supplier_performance_metrics(): void
    {
        Mail::fake();
        $admin = $this->user('admin');
        $supplier = $this->supplier(['lead_time_days' => 7]);
        $product = $this->product($supplier);

        // order 1: on time, everything delivered
        $onTime = $this->order($supplier, $admin, 'received', [[$product, 10]], [
            'order_date' => today()->subDays(30),
            'expected_delivery_date' => today()->subDays(23),
            'received_at' => today()->subDays(24)->setTime(10, 0),
        ]);
        $onTime->items()->update(['received_quantity' => 10]);

        // order 2: 3 days late, only 5 of 10 delivered
        $late = $this->order($supplier, $admin, 'received', [[$product, 10]], [
            'order_date' => today()->subDays(20),
            'expected_delivery_date' => today()->subDays(13),
            'received_at' => today()->subDays(10)->setTime(10, 0),
        ]);
        $late->items()->update(['received_quantity' => 5]);

        $metrics = (new SupplierPerformanceService())->forSupplier($supplier);

        $this->assertSame(2, $metrics['total_orders']);
        $this->assertSame(2, $metrics['delivered_orders']);
        $this->assertEquals(50.0, $metrics['on_time_rate']);
        $this->assertEquals(75.0, $metrics['accuracy_rate']);   // 15 of 20 pieces
        $this->assertEquals(8.0, $metrics['avg_actual_days']);  // (6 + 10) / 2
        $this->assertEquals(50.0, $supplier->performance_rating);

        $this->actingAs($admin)->get(route('reports.supplier-performance'))->assertOk()->assertSee($supplier->name);
    }

    // ---------- Part 7 and 8: filters and exports ----------

    public function test_purchase_order_list_can_be_filtered(): void
    {
        Mail::fake();
        $admin = $this->user('admin');
        $supplier = $this->supplier();
        $product = $this->product($supplier);
        $pending = $this->order($supplier, $admin, 'pending', [[$product, 5]]);
        $draft = $this->order($supplier, $admin, 'draft', [[$product, 5]]);

        $this->actingAs($admin)->get(route('purchase-orders.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee($pending->reference_number)
            ->assertDontSee($draft->reference_number);

        $this->actingAs($admin)->get(route('purchase-orders.index', ['q' => $draft->reference_number]))
            ->assertOk()
            ->assertSee($draft->reference_number)
            ->assertDontSee($pending->reference_number);
    }

    public function test_csv_exports_download(): void
    {
        Mail::fake();
        $admin = $this->user('admin');
        $supplier = $this->supplier();
        $product = $this->product($supplier, ['name' => 'Export Me Pen']);
        $order = $this->order($supplier, $admin, 'pending', [[$product, 5]]);

        $poCsv = $this->actingAs($admin)->get(route('exports.download', ['report' => 'purchase-orders', 'format' => 'csv']));
        $poCsv->assertOk();
        $this->assertStringContainsString($order->reference_number, $poCsv->streamedContent());

        $productCsv = $this->actingAs($admin)->get(route('exports.download', ['report' => 'products', 'format' => 'csv']));
        $productCsv->assertOk();
        $this->assertStringContainsString('Export Me Pen', $productCsv->streamedContent());

        $this->actingAs($admin)->get(route('exports.download', ['report' => 'supplier-performance', 'format' => 'csv']))->assertOk();
        $this->actingAs($admin)->get(route('exports.download', ['report' => 'transactions', 'format' => 'csv']))->assertOk();
        $this->actingAs($admin)->get(route('exports.download', ['report' => 'nonsense', 'format' => 'csv']))->assertNotFound();
    }

    public function test_notes_can_be_added_to_suppliers_and_orders_but_not_by_viewers(): void
    {
        Mail::fake();
        $staff = $this->user('staff');
        $supplier = $this->supplier();
        $product = $this->product($supplier);
        $order = $this->order($supplier, $staff, 'draft', [[$product, 5]]);

        $this->actingAs($staff)->post(route('notes.store'), ['noteable_type' => 'supplier', 'noteable_id' => $supplier->id, 'body' => 'Call before ordering'])
            ->assertSessionHas('success');
        $this->actingAs($staff)->post(route('notes.store'), ['noteable_type' => 'purchase-order', 'noteable_id' => $order->id, 'body' => 'Rush order'])
            ->assertSessionHas('success');

        $this->assertSame(1, $supplier->stickyNotes()->count());
        $this->assertSame(1, $order->stickyNotes()->count());

        $this->actingAs($this->user('viewer'))->post(route('notes.store'), ['noteable_type' => 'supplier', 'noteable_id' => $supplier->id, 'body' => 'x'])
            ->assertForbidden();
    }
}
