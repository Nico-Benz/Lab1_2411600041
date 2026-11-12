<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class Lab6InventoryTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function viewer(): User
    {
        return User::factory()->create(['role' => 'viewer']);
    }

    private function product(int $qty = 50, int $reorder = 10): Product
    {
        $category = Category::create(['name' => 'Paper Products', 'description' => 'x']);

        return Product::create([
            'name' => 'Notebook', 'sku' => 'T-' . uniqid(), 'category_id' => $category->id,
            'quantity' => $qty, 'reorder_level' => $reorder, 'unit_price' => 10,
        ]);
    }

    public function test_stock_in_increases_quantity_and_logs_before_after(): void
    {
        $admin = $this->admin();
        $p = $this->product(50);

        $this->actingAs($admin)->post(route('transactions.store'), [
            'product_id' => $p->id, 'transaction_type' => 'in', 'quantity' => 20, 'reference_number' => 'PO-1',
        ])->assertRedirect(route('transactions.index'));

        $this->assertSame(70, $p->fresh()->quantity);
        $t = InventoryTransaction::first();
        $this->assertSame(50, $t->quantity_before);
        $this->assertSame(70, $t->quantity_after);
        $this->assertSame($admin->id, $t->user_id);
    }

    public function test_stock_out_decreases_quantity(): void
    {
        $p = $this->product(50);
        $this->actingAs($this->admin())->post(route('transactions.store'), [
            'product_id' => $p->id, 'transaction_type' => 'out', 'quantity' => 15,
        ])->assertSessionHasNoErrors();

        $this->assertSame(35, $p->fresh()->quantity);
    }

    public function test_stock_out_more_than_available_is_rejected(): void
    {
        $p = $this->product(5);
        $this->actingAs($this->admin())->post(route('transactions.store'), [
            'product_id' => $p->id, 'transaction_type' => 'out', 'quantity' => 9,
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(5, $p->fresh()->quantity);
        $this->assertSame(0, InventoryTransaction::count());
    }

    public function test_observer_blocks_negative_stock_and_rolls_back(): void
    {
        $admin = $this->admin();
        $p = $this->product(5);

        try {
            DB::transaction(function () use ($p, $admin) {
                InventoryTransaction::create([
                    'product_id' => $p->id, 'transaction_type' => 'out', 'quantity' => 99, 'user_id' => $admin->id,
                ]);
            });
            $this->fail('Exception expected');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Not enough stock', $e->getMessage());
        }

        $this->assertSame(5, $p->fresh()->quantity);
        $this->assertSame(0, InventoryTransaction::count());
    }

    public function test_low_stock_alert_is_sent_once_when_crossing_reorder_level(): void
    {
        $admin = $this->admin();
        $viewer = $this->viewer();
        $p = $this->product(15, 10);

        // 15 -> 8 crosses the reorder level (10): alert
        $this->actingAs($admin)->post(route('transactions.store'), ['product_id' => $p->id, 'transaction_type' => 'out', 'quantity' => 7]);
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(0, $viewer->notifications()->count()); // viewers do not get alerts
        $this->assertSame($p->id, $admin->notifications()->first()->data['product_id']);

        // 8 -> 5 is already low: no duplicate alert
        $this->actingAs($admin)->post(route('transactions.store'), ['product_id' => $p->id, 'transaction_type' => 'out', 'quantity' => 3]);
        $this->assertSame(1, $admin->notifications()->count());
    }

    public function test_adjustment_records_the_difference(): void
    {
        $p = $this->product(78);
        $this->actingAs($this->admin())->post(route('adjustments.store'), [
            'product_id' => $p->id, 'physical_count' => 80, 'reason' => 'Physical count discrepancy',
        ])->assertSessionHasNoErrors();

        $this->assertSame(80, $p->fresh()->quantity);
        $t = InventoryTransaction::first();
        $this->assertSame('adjustment', $t->transaction_type);
        $this->assertSame(2, $t->signed_change);
        $this->assertSame(2, $t->quantity);

        // same count again: rejected
        $this->post(route('adjustments.store'), [
            'product_id' => $p->id, 'physical_count' => 80, 'reason' => 'Damaged goods',
        ])->assertSessionHasErrors('physical_count');
    }

    public function test_adjustment_can_lower_stock(): void
    {
        $p = $this->product(30);
        $this->actingAs($this->admin())->post(route('adjustments.store'), [
            'product_id' => $p->id, 'physical_count' => 25, 'reason' => 'Damaged goods',
        ]);
        $this->assertSame(25, $p->fresh()->quantity);
        $this->assertSame(-5, InventoryTransaction::first()->signed_change);
    }

    public function test_admin_only_pages_return_403_for_viewers(): void
    {
        $viewer = $this->viewer();
        $p = $this->product();

        $this->actingAs($viewer);
        $this->get(route('products.create'))->assertForbidden();
        $this->get(route('products.edit', $p))->assertForbidden();
        $this->get(route('adjustments.create'))->assertForbidden();
        $this->get(route('transactions.create'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
        $this->delete(route('products.destroy', $p))->assertForbidden();

        // viewers cannot record stock movements either
        $this->post(route('transactions.store'), [
            'product_id' => $p->id, 'transaction_type' => 'in', 'quantity' => 1,
        ])->assertForbidden();
        $this->assertSame(50, $p->fresh()->quantity);

        // but viewers can still view normal pages
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('products.index'))->assertOk();
        $this->get(route('products.show', $p))->assertOk();
        $this->get(route('transactions.index'))->assertOk();
        $this->get(route('reports.index'))->assertOk();
    }

    public function test_admin_can_open_admin_pages(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('products.create'))->assertOk();
        $this->get(route('adjustments.create'))->assertOk();
        $this->get(route('users.index'))->assertOk();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/transactions')->assertRedirect('/login');
        $this->get('/api/dashboard/stats')->assertRedirect('/login');
    }

    public function test_api_endpoints_return_json(): void
    {
        $admin = $this->admin();
        $p = $this->product(3, 10);
        $this->actingAs($admin)->post(route('transactions.store'), ['product_id' => $p->id, 'transaction_type' => 'in', 'quantity' => 1]);

        $this->getJson('/api/dashboard/stats')->assertOk()
            ->assertJsonStructure(['stats' => ['total_products', 'low_stock', 'out_of_stock', 'inventory_value'], 'unread_notifications', 'generated_at'])
            ->assertJsonPath('stats.total_products', 1)
            ->assertJsonPath('stats.low_stock', 1);

        $this->getJson('/api/products/low-stock')->assertOk()
            ->assertJsonPath('data.0.id', $p->id)->assertJsonPath('data.0.status', 'Low Stock');

        $this->getJson('/api/transactions/recent')->assertOk()
            ->assertJsonPath('data.0.type', 'Stock In')->assertJsonPath('data.0.change', 1);

        $this->getJson("/api/products/{$p->id}/stock")->assertOk()->assertJsonPath('quantity', 4);
    }

    public function test_pages_render_with_filters_and_reports_export(): void
    {
        $admin = $this->admin();
        $p = $this->product(20);
        $this->actingAs($admin)->post(route('transactions.store'), ['product_id' => $p->id, 'transaction_type' => 'out', 'quantity' => 4]);
        $t = InventoryTransaction::first();

        $this->get(route('transactions.index', ['type' => 'out', 'product_id' => $p->id, 'from' => now()->subDay()->toDateString(), 'to' => now()->toDateString()]))
            ->assertOk()->assertSee('Stock Out');
        $this->get(route('transactions.show', $t))->assertOk()->assertSee('Transaction #' . $t->id);
        $this->get(route('products.show', ['product' => $p, 'from' => now()->subDay()->toDateString()]))->assertOk()->assertSee('Balance after');
        $this->get(route('reports.index'))->assertOk()->assertSee('Inventory Valuation Report');
        foreach (['movements', 'low-stock', 'valuation'] as $type) {
            $this->get(route('reports.export', $type))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }
    }

    public function test_deleting_latest_transaction_reverses_stock_and_older_ones_are_protected(): void
    {
        $admin = $this->admin();
        $p = $this->product(20);
        $this->actingAs($admin);
        $this->post(route('transactions.store'), ['product_id' => $p->id, 'transaction_type' => 'out', 'quantity' => 5]);
        $this->post(route('transactions.store'), ['product_id' => $p->id, 'transaction_type' => 'in', 'quantity' => 2]);
        [$first, $second] = [InventoryTransaction::orderBy('id')->first(), InventoryTransaction::orderByDesc('id')->first()];
        $this->assertSame(17, $p->fresh()->quantity);

        $this->delete(route('transactions.destroy', $first))->assertSessionHas('error');
        $this->assertSame(17, $p->fresh()->quantity);

        $this->delete(route('transactions.destroy', $second))->assertSessionHas('success');
        $this->assertSame(15, $p->fresh()->quantity);
    }

    public function test_notification_can_be_marked_as_read(): void
    {
        $admin = $this->admin();
        $p = $this->product(12, 10);
        $this->actingAs($admin)->post(route('transactions.store'), ['product_id' => $p->id, 'transaction_type' => 'out', 'quantity' => 5]);
        $n = $admin->unreadNotifications()->first();

        $this->post(route('notifications.read', $n->id))->assertRedirect(route('products.show', $p->id));
        $this->assertSame(0, $admin->unreadNotifications()->count());
    }

    public function test_validation_rules(): void
    {
        $p = $this->product();
        $this->actingAs($this->admin());
        $this->post(route('transactions.store'), [])->assertSessionHasErrors(['product_id', 'transaction_type', 'quantity']);
        $this->post(route('transactions.store'), ['product_id' => $p->id, 'transaction_type' => 'in', 'quantity' => -5])->assertSessionHasErrors('quantity');
        $this->post(route('transactions.store'), ['product_id' => $p->id, 'transaction_type' => 'in', 'quantity' => 'abc'])->assertSessionHasErrors('quantity');
        $this->post(route('transactions.store'), ['product_id' => 9999, 'transaction_type' => 'in', 'quantity' => 1])->assertSessionHasErrors('product_id');
    }
}