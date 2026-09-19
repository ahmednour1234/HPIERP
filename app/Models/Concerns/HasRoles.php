<?php

namespace App\Models\Concerns;

use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

/**
 * أدوار المستخدم وصلاحياته.
 *
 * تُحمَّل الصلاحيات مرة واحدة لكل طلب: الشاشة تسأل عن العشرات منها لإخفاء
 * الأزرار، واستعلام لكل سؤال يضاعف زمن الصفحة.
 */
trait HasRoles
{
    /** @var array<string, bool>|null */
    private ?array $permissionCache = null;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_admin', 'admin_id', 'role_id');
    }

    /**
     * أسماء صلاحيات هذا المستخدم، مجموعة من كل أدواره.
     *
     * @return array<string, bool>
     */
    public function permissionNames(): array
    {
        if ($this->permissionCache !== null) {
            return $this->permissionCache;
        }

        $names = DB::table('role_admin')
            ->join('permission_role', 'permission_role.role_id', '=', 'role_admin.role_id')
            ->join('permissions', 'permissions.id', '=', 'permission_role.permission_id')
            ->where('role_admin.admin_id', $this->id)
            ->distinct()
            ->pluck('permissions.name')
            ->all();

        return $this->permissionCache = array_fill_keys($names, true);
    }

    /**
     * هل يملك هذه الصلاحية؟
     *
     * صلاحية النظام الكاملة تُغني عن كل ما عداها، وكذلك العمود is_super
     * الذي ما زال يميّز المالك في البيانات القائمة.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->is_super) {
            return true;
        }

        $names = $this->permissionNames();

        return isset($names[Permissions::SUPER]) || isset($names[$permission]);
    }

    /** هل يملك أيًّا من هذه الصلاحيات؟ */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * هل يرى هذا القسم؟ أي يملك أي صلاحية داخله.
     *
     * القائمة الجانبية تسأل بالمجموعة لا بالفعل: من يملك حق الإضافة وحده
     * يجب أن يرى القسم ليصل إليها.
     */
    public function canAccessGroup(string $group): bool
    {
        if ($this->is_super) {
            return true;
        }

        $names = $this->permissionNames();

        if (isset($names[Permissions::SUPER])) {
            return true;
        }

        $prefix = $group . '.';

        foreach (array_keys($names) as $name) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }
}
