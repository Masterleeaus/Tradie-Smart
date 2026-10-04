<?php

namespace Tests\Feature\FSM;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Titan\Support\TitanFakeUser;
use Tests\TestCase;

class FsmTargetModulePromotionBootstrapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Schema::dropAllTables();

        Schema::create('companies', function ($table) {
            $table->id();
            $table->string('date_format')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name')->nullable();
            $table->string('image')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('fsm_orders', function ($table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->unsignedBigInteger('stage_id')->nullable();
            $table->dateTime('date_start')->nullable();
            $table->dateTime('date_end')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function ($table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('project_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tasks', function ($table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('heading')->nullable();
            $table->string('status')->default('incomplete');
            $table->dateTime('completed_on')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('task_users', function ($table) {
            $table->id();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('fsm_sizes', function ($table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('code', 8);
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('fsm_frequencies', function ($table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('interval')->default(1);
            $table->string('interval_type', 20)->default('weekly');
            $table->boolean('is_exclusive')->default(false);
            $table->boolean('use_bymonthday')->default(false);
            $table->unsignedTinyInteger('month_day')->nullable();
            $table->boolean('use_byweekday')->default(false);
            $table->boolean('weekday_mo')->default(false);
            $table->boolean('weekday_tu')->default(false);
            $table->boolean('weekday_we')->default(false);
            $table->boolean('weekday_th')->default(false);
            $table->boolean('weekday_fr')->default(false);
            $table->boolean('weekday_sa')->default(false);
            $table->boolean('weekday_su')->default(false);
            $table->boolean('use_bymonth')->default(false);
            $table->boolean('month_jan')->default(false);
            $table->boolean('month_feb')->default(false);
            $table->boolean('month_mar')->default(false);
            $table->boolean('month_apr')->default(false);
            $table->boolean('month_may')->default(false);
            $table->boolean('month_jun')->default(false);
            $table->boolean('month_jul')->default(false);
            $table->boolean('month_aug')->default(false);
            $table->boolean('month_sep')->default(false);
            $table->boolean('month_oct')->default(false);
            $table->boolean('month_nov')->default(false);
            $table->boolean('month_dec')->default(false);
            $table->boolean('use_setpos')->default(false);
            $table->integer('set_pos')->nullable();
            $table->timestamps();
        });

        Schema::create('fsm_skill_types', function ($table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name', 128);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('sessions', function ($table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->mediumText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('client_contacts', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
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

        DB::table('companies')->insert([
            'id' => 1,
            'date_format' => 'Y-m-d',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fsm_orders')->insert([
            'id' => 101,
            'company_id' => 1,
            'name' => 'Order A',
            'project_id' => null,
            'task_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('projects')->insert([
            'id' => 555,
            'company_id' => 1,
            'project_name' => 'FSM Linked Project',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('projects')->insert([
            'id' => 556,
            'company_id' => 1,
            'project_name' => 'Another Project',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tasks')->insert([
            'id' => 777,
            'company_id' => 1,
            'project_id' => 555,
            'heading' => 'FSM Linked Task',
            'status' => 'incomplete',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tasks')->insert([
            'id' => 778,
            'company_id' => 1,
            'project_id' => 556,
            'heading' => 'Different Project Task',
            'status' => 'incomplete',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withoutMiddleware();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->actingAs(new TitanFakeUser(11, 1, ['admin']));
    }

    public function test_target_routes_bootstrap_for_promoted_modules(): void
    {
        $this->assertTrue(Route::has('fsmworkflow.sizes.index'));
        $this->assertTrue(Route::has('fsmworkflow.sizes.store'));
        $this->assertTrue(Route::has('fsmworkflow.sizes.update'));
        $this->assertTrue(Route::has('fsmworkflow.sizes.destroy'));
        $this->assertTrue(Route::has('fsmworkflow.stage_actions.index'));
        $this->assertTrue(Route::has('fsmworkflow.kanban_config.index'));
        $this->assertTrue(Route::has('fsmrecurring.frequencies.index'));
        $this->assertTrue(Route::has('fsmskill.skill-types.index'));
    }

    public function test_fsmworkflow_size_web_render_and_save_flow(): void
    {
        $controller = app(\Modules\FSMWorkflow\Http\Controllers\SizeController::class);
        $indexResponse = $controller->index(Request::create(route('fsmworkflow.sizes.index'), 'GET'));
        $this->assertInstanceOf(View::class, $indexResponse);

        $storeResponse = $controller->store(Request::create(route('fsmworkflow.sizes.store'), 'POST', [
            'code' => 'SML',
            'name' => 'Small Apartment',
            'unit_of_measure' => 'sqm',
            'is_order_size' => 1,
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $storeResponse);
        $this->assertSame(route('fsmworkflow.sizes.index'), $storeResponse->getTargetUrl());

        $this->assertDatabaseHas('fsm_sizes', [
            'code' => 'SML',
            'name' => 'Small Apartment',
        ]);
    }

    public function test_fsmrecurring_frequency_web_render_and_save_flow(): void
    {
        $controller = app(\Modules\FSMRecurring\Http\Controllers\FrequencyController::class);
        $indexResponse = $controller->index();
        $this->assertInstanceOf(View::class, $indexResponse);

        $storeResponse = $controller->store(Request::create(route('fsmrecurring.frequencies.store'), 'POST', [
            'name' => 'Weekly',
            'interval' => 1,
            'interval_type' => 'weekly',
            'active' => 1,
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $storeResponse);
        $this->assertSame(route('fsmrecurring.frequencies.index'), $storeResponse->getTargetUrl());

        $this->assertDatabaseHas('fsm_frequencies', [
            'company_id' => 1,
            'name' => 'Weekly',
        ]);
    }

    public function test_fsmskill_type_web_render_and_save_flow(): void
    {
        $controller = app(\Modules\FSMSkill\Http\Controllers\SkillTypeController::class);
        $indexResponse = $controller->index();
        $this->assertInstanceOf(View::class, $indexResponse);

        $storeResponse = $controller->store(Request::create(route('fsmskill.skill-types.store'), 'POST', [
            'name' => 'Cleaning',
            'active' => 1,
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $storeResponse);
        $this->assertSame(route('fsmskill.skill-types.index'), $storeResponse->getTargetUrl());

        $this->assertDatabaseHas('fsm_skill_types', [
            'company_id' => 1,
            'name' => 'Cleaning',
        ]);
    }
}
