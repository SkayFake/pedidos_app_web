<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_users', 'email')) {
                $table->string('email', 150)->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('admin_users', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete()->after('id');
            }
            if (!Schema::hasColumn('admin_users', 'role')) {
                $table->enum('role', ['super_admin', 'branch_admin', 'operator'])->default('super_admin')->after('password');
            }
            if (!Schema::hasColumn('admin_users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }
        });

        // Set default email for the admin user to allow login
        if (Schema::hasColumn('admin_users', 'username')) {
            DB::table('admin_users')->where('username', 'admin')->update([
                'email' => 'admin@gmail.com',
                'role' => 'super_admin',
                'is_active' => true,
            ]);
        } else {
            DB::table('admin_users')->where('id', 1)->update([
                'email' => 'admin@gmail.com',
                'role' => 'super_admin',
                'is_active' => true,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['email', 'branch_id', 'role', 'is_active']);
        });
    }
};
