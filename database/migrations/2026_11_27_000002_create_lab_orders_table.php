<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('test_name');
            $table->string('category')->nullable();
            $table->string('priority')->default('routine'); // routine | urgent | stat
            $table->string('status')->default('pending')->index(); // pending | in_progress | completed | cancelled
            $table->text('notes')->nullable();
            $table->text('result_summary')->nullable();
            $table->text('result_notes')->nullable();
            $table->timestamp('ordered_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['hospital_id', 'status', 'ordered_at'], 'lab_orders_hospital_status_idx');
            $table->index(['patient_id', 'ordered_at'], 'lab_orders_patient_ordered_idx');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(['lab.view', 'lab.manage'])
            ->map(fn (string $name) => Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]));

        foreach (['Lab Technician', 'Doctor'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = Permission::query()
            ->whereIn('name', ['lab.view', 'lab.manage'])
            ->where('guard_name', 'web')
            ->get();

        Role::query()
            ->whereIn('name', ['Lab Technician', 'Doctor'])
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permissions));

        Permission::query()
            ->whereIn('name', ['lab.view', 'lab.manage'])
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::dropIfExists('lab_orders');
    }
};
