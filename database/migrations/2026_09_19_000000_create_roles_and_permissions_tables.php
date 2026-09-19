<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * أدوار وصلاحيات بدل الأعمدة المنطقية على جدول admins.
     *
     * كانت الصلاحيات 33 عمودًا ثابتًا، فإضافة صلاحية تعني هجرة جديدة،
     * ولا يمكن تجميعها في دور يُعاد استعماله. الأعمدة القديمة تبقى في
     * مكانها ولا تُحذف: الترحيل يقرأ منها، وحذفها يمنع أي رجوع.
     */
    public function up()
    {
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();

                // مثل: accounts.view / accounts.create
                $table->string('name', 120)->unique();

                $table->string('group', 60)->index();
                $table->string('label', 160);

                $table->timestamps();
            });
        }

        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name', 80)->unique();
                $table->string('label', 160);
                $table->text('description')->nullable();

                // دور النظام لا يُحذف من الشاشة: حذف "مدير عام" يترك
                // النظام بلا أحد يملك إدارة الأدوار.
                $table->boolean('is_locked')->default(false);

                $table->timestamps();
            });
        }

        if (!Schema::hasTable('permission_role')) {
            Schema::create('permission_role', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('permission_id');

                $table->unique(['role_id', 'permission_id'], 'permission_role_unique');
                $table->index('permission_id');
            });
        }

        if (!Schema::hasTable('role_admin')) {
            Schema::create('role_admin', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('admin_id');

                $table->unique(['role_id', 'admin_id'], 'role_admin_unique');
                $table->index('admin_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('role_admin');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
