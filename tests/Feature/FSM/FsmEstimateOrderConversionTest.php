<?php

namespace Tests\Feature\FSM;

use App\Models\Estimate;
use App\Models\Invoice;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\FSMCore\Models\FSMOrder;
use Modules\FSMSales\Services\InvoiceGenerationService;
use Tests\Feature\Titan\Support\TitanFakeUser;
use Tests\TestCase;

class FsmEstimateOrderConversionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Schema::dropAllTables();

        Schema::create('users', function ($table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('active');
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('estimates', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedInteger('client_id')->nullable();
            $table->string('estimate_number')->nullable();
            $table->string('status')->default('draft');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('estimate_items', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('estimate_id');
            $table->string('item_name')->nullable();
            $table->timestamps();
        });

        Schema::create('fsm_orders', function ($table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name')->nullable();
            $table->unsignedInteger('person_id')->nullable();
            $table->unsignedInteger('estimate_id')->nullable();
            $table->string('billing_policy')->nullable();
            $table->decimal('billing_amount', 12, 2)->nullable();
            $table->boolean('is_invoiced')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('currencies', function ($table) {
            $table->increments('id');
            $table->string('currency_symbol')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('original_invoice_number')->nullable();
            $table->unsignedInteger('client_id')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->nullable();
            $table->unsignedInteger('currency_id')->nullable();
            $table->unsignedInteger('default_currency_id')->nullable();
            $table->decimal('sub_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('discount_type')->default('percent');
            $table->string('recurring')->default('no');
            $table->unsignedBigInteger('fsm_order_id')->nullable();
            $table->unsignedInteger('estimate_id')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_items', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedInteger('invoice_id');
            $table->unsignedBigInteger('fsm_order_id')->nullable();
            $table->string('type')->nullable();
            $table->string('item_name')->nullable();
            $table->text('item_summary')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('invoice_item_images', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('invoice_item_id');
            $table->string('filename')->nullable();
            $table->timestamps();
        });

        Schema::create('fsm_order_invoice', function ($table) {
            $table->id();
            $table->unsignedBigInteger('fsm_order_id');
            $table->unsignedInteger('invoice_id');
        });

        DB::table('users')->insert([
            'id' => 11,
            'company_id' => 1,
            'name' => 'FSM Tenant User',
            'email' => 'fsm-user@example.test',
            'password' => 'x',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withoutMiddleware();
        $this->actingAs(new TitanFakeUser(11, 1, ['admin']));

        Invoice::flushEventListeners();
    }

    public function test_invoice_generation_carries_estimate_id_from_order(): void
    {
        DB::table('estimates')->insert([
            'id' => 303,
            'company_id' => 1,
            'client_id' => 11,
            'estimate_number' => 'EST-303',
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fsm_orders')->insert([
            'id' => 404,
            'company_id' => 1,
            'name' => 'ORD-00404',
            'person_id' => 11,
            'estimate_id' => 303,
            'billing_policy' => 'on_completion',
            'billing_amount' => 150,
            'is_invoiced' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(InvoiceGenerationService::class);
        $invoice = $service->createFromOrderCompletion(FSMOrder::query()->findOrFail(404));

        $this->assertSame(303, (int) $invoice->estimate_id);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'estimate_id' => 303,
            'fsm_order_id' => 404,
        ]);
        $this->assertDatabaseHas('fsm_orders', [
            'id' => 404,
            'is_invoiced' => 1,
        ]);
        $this->assertSame('accepted', (string) Estimate::query()->findOrFail(303)->status);
    }
}
