<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\EnforceSectionPermission;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

/**
 * تغطية الحماية: كل مسار في اللوحة يقع تحت قسم معروف.
 *
 * الحماية كانت على 26 مسارًا من 327، فبقي الباقي مفتوحًا لمن يكتب
 * الرابط. هذا الاختبار يُسقط أي مسار جديد يُضاف بلا تصنيف، فلا تعود
 * الثغرة بصمت.
 */
class RouteCoverageTest extends TestCase
{
    public function test_every_admin_route_belongs_to_a_known_section(): void
    {
        $middleware = new EnforceSectionPermission();
        $method = new ReflectionMethod($middleware, 'groupFor');
        $method->setAccessible(true);

        $groups = array_keys(Permissions::groups());
        $unclassified = [];
        $unknownGroup = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (!str_starts_with($uri, 'admin')) {
                continue;
            }

            $group = $method->invoke($middleware, Request::create('/' . $uri, 'GET'));

            if ($group === null) {
                continue; // مسار مفتوح عمدًا: الدخول والصفحة الرئيسية
            }

            if ($group === '__unclassified__') {
                $unclassified[] = $uri;
                continue;
            }

            // البادئة تشير إلى مجموعة غير موجودة في السجل: الصلاحية لن
            // تُزرع أبدًا، فلا أحد يملكها ويُحجب الجميع.
            if (!in_array($group, $groups, true)) {
                $unknownGroup[$uri] = $group;
            }
        }

        $this->assertSame([], $unclassified,
            "مسارات بلا تصنيف:\n  " . implode("\n  ", $unclassified));

        $this->assertSame([], $unknownGroup,
            'بادئات تشير إلى مجموعات غير مسجَّلة: ' . json_encode($unknownGroup, JSON_UNESCAPED_UNICODE));
    }

    /** كل مجموعة في السجل تملك صلاحية عرض على الأقل. */
    public function test_every_group_has_a_view_permission(): void
    {
        $missing = [];

        foreach (Permissions::groups() as $group => $meta) {
            if (!array_key_exists('view', $meta['actions'])) {
                $missing[] = $group;
            }
        }

        $this->assertSame([], $missing,
            'مجموعات بلا صلاحية عرض، فلا تظهر في القائمة: ' . implode(', ', $missing));
    }
}
