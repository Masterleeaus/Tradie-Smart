<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duplicatePermissionIds = DB::table('user_permissions as p1')
            ->join('user_permissions as p2', function ($join) {
                $join->on('p1.id', '>', 'p2.id')
                    ->on('p1.permission_id', '=', 'p2.permission_id')
                    ->on('p1.user_id', '=', 'p2.user_id');
            })
            ->distinct()
            ->pluck('p1.id');

        if ($duplicatePermissionIds->isNotEmpty()) {
            DB::table('user_permissions')
                ->whereIn('id', $duplicatePermissionIds)
                ->delete();
        }

        // Step 2: Add unique constraint
        Schema::table('user_permissions', function (Blueprint $table) {
            $table->unique(['permission_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
