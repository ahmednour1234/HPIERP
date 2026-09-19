<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Console\Command;

/**
 * منح صلاحية السوبر أدمن أو سحبها من السطر.
 *
 * الشاشة لا تكفي هنا: لو لم يبق في النظام من يفتح شاشة الأدوار فلا
 * سبيل لمنح أحد شيئًا منها. هذا الأمر هو الباب الخلفي المقصود، يُشغَّل
 * على الخادم بعد النشر أو عند فقد الوصول.
 */
class GrantSuperAdmin extends Command
{
    protected $signature = 'permissions:super
                            {admin? : البريد الإلكتروني أو رقم المستخدم}
                            {--revoke : يسحب الدور بدل منحه}
                            {--list : يعرض السوبر أدمنز الحاليين}';

    protected $description = 'يمنح مستخدمًا كل الصلاحيات (سوبر أدمن) أو يسحبها منه';

    public function handle(): int
    {
        $role = Role::where('name', 'super-admin')->first();

        // الدور يُنشأ بالبذرة. غيابه يعني أن النظام لم يُهيّأ بعد،
        // وإنشاؤه هنا صامتًا يخفي ذلك بدل أن يصلحه.
        if (! $role) {
            $this->error('دور super-admin غير موجود — شغّل أولًا: php artisan permissions:sync');

            return self::FAILURE;
        }

        if ($this->option('list')) {
            return $this->listSupers($role);
        }

        $needle = $this->argument('admin')
            ?: $this->ask('البريد الإلكتروني أو رقم المستخدم');

        if (! $admin = $this->find($needle)) {
            return self::FAILURE;
        }

        return $this->option('revoke')
            ? $this->revoke($admin, $role)
            : $this->grant($admin, $role);
    }

    /**
     * البحث بالرقم أو بالبريد.
     *
     * البريد يُطابق جزئيًا أيضًا: بعض الحسابات هنا تحمل لواحق مثل
     * "admin2@gmail.com00"، فالمطابقة التامة وحدها تُفشل الأمر على من
     * يكتب البريد كما يراه.
     */
    private function find(string $needle): ?Admin
    {
        $needle = trim($needle);

        if (ctype_digit($needle) && $admin = Admin::find((int) $needle)) {
            return $admin;
        }

        $matches = Admin::where('email', $needle)
            ->orWhere('email', 'like', $needle . '%')
            ->get();

        if ($matches->isEmpty()) {
            $this->error("لا يوجد مستخدم بـ: {$needle}");

            return null;
        }

        // أكثر من مطابقة: الاختيار للمستخدم لا للأمر.
        if ($matches->count() > 1) {
            $this->warn('أكثر من مستخدم يطابق:');
            $this->rows($matches);
            $this->line('  أعد المحاولة برقم المستخدم.');

            return null;
        }

        return $matches->first();
    }

    private function grant(Admin $admin, Role $role): int
    {
        if ($admin->roles()->where('roles.id', $role->id)->exists()) {
            $this->info("{$this->label($admin)} سوبر أدمن بالفعل.");

            return self::SUCCESS;
        }

        // بلا detach: أدواره الأخرى تبقى، فسحب السوبر لاحقًا يعيده إلى
        // ما كان عليه بدل أن يتركه بلا شيء.
        $admin->roles()->syncWithoutDetaching([$role->id]);

        $this->info("✅ {$this->label($admin)} صار سوبر أدمن.");
        $this->verify($admin);

        return self::SUCCESS;
    }

    private function revoke(Admin $admin, Role $role): int
    {
        $admin->roles()->detach($role->id);

        $this->info("تم سحب السوبر أدمن من {$this->label($admin)}.");

        $remaining = $admin->fresh()->roles->pluck('label')->implode('، ');

        $this->line('  الأدوار المتبقية: ' . ($remaining ?: 'لا شيء — لن يرى اللوحة'));

        // عمود is_super القديم يتجاوز الأدوار، فسحب الدور وحده لا يكفي.
        if ($admin->is_super) {
            $this->warn('  لديه is_super=1 في جدول admins، وهو يتجاوز الأدوار.');
        }

        return self::SUCCESS;
    }

    private function listSupers(Role $role): int
    {
        $byRole   = $role->admins()->get();
        $byColumn = Admin::where('is_super', 1)->get();

        $all = $byRole->merge($byColumn)->unique('id');

        if ($all->isEmpty()) {
            $this->warn('لا يوجد سوبر أدمن — لا أحد يستطيع توزيع الصلاحيات.');

            return self::SUCCESS;
        }

        $this->rows($all, true);

        return self::SUCCESS;
    }

    /** تأكيد فعلي بعد المنح: الدور قد يوجد والصلاحية لا تصل. */
    private function verify(Admin $admin): void
    {
        $admin = $admin->fresh();
        $blocked = array_filter(
            array_keys(Permissions::groups()),
            fn ($group) => ! $admin->canAccessGroup($group)
        );

        $this->line('  الصلاحية الشاملة: '
            . ($admin->hasPermission(Permissions::SUPER) ? 'نعم' : 'لا'));

        $this->line('  الأقسام: ' . count(Permissions::groups()) . ' قسم، محجوب: '
            . (count($blocked) ? implode('، ', $blocked) : 'لا شيء'));
    }

    private function rows($admins, bool $showSource = false): void
    {
        $headers = ['#', 'الاسم', 'البريد'];

        if ($showSource) {
            $headers[] = 'المصدر';
        }

        $this->table($headers, $admins->map(function (Admin $a) use ($showSource) {
            $row = [$a->id, trim($a->f_name . ' ' . $a->l_name), $a->email];

            if ($showSource) {
                $row[] = $a->is_super ? 'عمود is_super' : 'دور super-admin';
            }

            return $row;
        })->all());
    }

    private function label(Admin $admin): string
    {
        return '#' . $admin->id . ' ' . trim($admin->f_name . ' ' . $admin->l_name);
    }
}
