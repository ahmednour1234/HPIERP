<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Console\Command;

/**
 * مزامنة الصلاحيات والأدوار.
 *
 * تُشغَّل بعد كل نشر يضيف قسمًا جديدًا: أمر واحد أوضح من تذكّر اسم صف
 * البذرة، ويعرض بعده من لا دور له — وهو ما يُقعد المستخدم عن اللوحة
 * كلها بلا رسالة مفهومة.
 */
class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync {--show : يعرض الحالة دون أي كتابة}';

    protected $description = 'يزرع الصلاحيات والأدوار ويرحّل الصلاحيات القديمة';

    public function handle(): int
    {
        if ($this->option('show')) {
            return $this->report();
        }

        $this->info('مزامنة الصلاحيات...');

        $this->callSilent('db:seed', [
            '--class' => RolesPermissionsSeeder::class,
            '--force' => true,
        ]);

        $this->newLine();

        return $this->report();
    }

    private function report(): int
    {
        $registered = count(Permissions::all());
        $stored     = Permission::count();

        $this->table(['', 'العدد'], [
            ['الصلاحيات في السجل', $registered],
            ['الصلاحيات في الجدول', $stored],
            ['الأدوار', Role::count()],
            ['مستخدمون لهم أدوار', Admin::has('roles')->count()],
        ]);

        // فرق بين السجل والجدول يعني بذرة لم تُشغَّل بعد إضافة قسم.
        if ($stored !== $registered) {
            $this->warn('الجدول لا يطابق السجل — شغّل: php artisan permissions:sync');
        }

        // مستخدم بلا دور لا يرى شيئًا، وهي حالة صامتة يصعب تشخيصها.
        $orphans = Admin::where('role', '!=', 'seller')
            ->where('is_super', '!=', 1)
            ->doesntHave('roles')
            ->get(['id', 'f_name', 'l_name', 'email']);

        if ($orphans->isNotEmpty()) {
            $this->newLine();
            $this->warn('مستخدمون بلا أي دور — لن يروا شيئًا في اللوحة:');

            $this->table(['#', 'الاسم', 'البريد'], $orphans->map(fn ($a) => [
                $a->id,
                trim($a->f_name . ' ' . $a->l_name),
                $a->email,
            ])->all());

            $this->line('  أسند لهم دورًا من: admin/roles/assign');
        }

        return self::SUCCESS;
    }
}
