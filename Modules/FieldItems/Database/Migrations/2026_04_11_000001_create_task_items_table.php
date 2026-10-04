<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the task_items pivot table for FSM job consumption tracking.
     * Records which field items (materials, chemicals, equipment) were
     * consumed on each task/booking.
     */
    public function up(): void
    {
        if (Schema::hasTable('task_items')) {
            return;
        }

        Schema::create('task_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('task_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->unsignedInteger('company_id')->nullable();
            $table->timestamps();

            // FK to tasks (core table)
            if (Schema::hasTable('tasks')) {
                $table->foreign('task_id')
                    ->references('id')
                    ->on('tasks')
                    ->onDelete('cascade');
            }

            // FK to items (this module)
            $table->foreign('item_id')
                ->references('id')
                ->on('items')
                ->onDelete('cascade');

            // FK to companies (tenant scoping)
            if (Schema::hasTable('companies')) {
                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('set null');
            }

            $table->index(['task_id', 'item_id']);
            $table->index(['company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_items');
    }
};
