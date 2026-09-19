<?php

namespace App\Providers;
ini_set('memory_limit', '-1');
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use App\Models\BusinessSetting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        $this->registerPermissionDirectives();

        try {
            // كان هذا استعلامًا في كل طلب مهما كان مساره. القيمة شبه ثابتة،
            // فتخزينها مؤقتًا يوفّر استعلامًا على كل صفحة.
            $timezone = \Illuminate\Support\Facades\Cache::remember(
                'app.time_zone',
                600,
                fn () => optional(BusinessSetting::where(['key' => 'time_zone'])->first())->value
            );

            if (!empty($timezone)) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }
        } catch (\Exception $exception) {
        }

        // الترويسة والقائمة الجانبية كانتا تنفّذان عشرات الاستعلامات في كل
        // صفحة. الخدمة تجمعها في استعلامات معدودة مخزَّنة مؤقتًا، وتُشارَك
        // هنا مع كل القوالب.
        View::composer(
            ['layouts.admin.partials._header', 'layouts.admin.partials._sidebar'],
            function ($view) {
                $badges = app(\App\Services\AdminBadgeCounts::class);

                $view->with('badgeCounts', $badges->counts())
                     ->with('badgeService', $badges);
            }
        );
    }

    /**
     * توجيهات Blade لإخفاء الأزرار والأقسام حسب الصلاحية.
     *
     *   @haspermission('accounts.create') ... @endhaspermission
     *   @hasanypermission(['a.view','b.view']) ... @endhasanypermission
     *   @cangroup('accounts') ... @endcangroup
     *
     * توجيهات خاصة لا @can المدمجة: تلك تسأل حارس web الافتراضي، وهذه
     * اللوحة تسجّل الدخول على حارس admin، فكانت @can ترجع false دائمًا.
     */
    private function registerPermissionDirectives(): void
    {
        // بوابة واحدة تلتقط كل الصلاحيات المسمّاة، فلا نُعرّف واحدة لكل
        // صلاحية من 119. تفيد Gate::forUser و authorize() في المتحكمات.
        Gate::before(function ($user, string $ability) {
            if (!method_exists($user, 'hasPermission')) {
                return null;
            }

            // null لا false: إرجاع false هنا يُسقط أي بوابة أخرى.
            return $user->hasPermission($ability) ? true : null;
        });

        // Blade::if() هنا كانت تسجّل @end… لكن المُصرِّف لا يطبّقها،
        // فيبقى الوسم النصي ويكسر القالب. التوجيهات الصريحة تُصرَّف
        // كما هي، وهي في النهاية @if/@endif عاديتان.
        Blade::directive('haspermission', fn ($expression) =>
            "<?php if (\\App\\Support\\Perm::has({$expression})): ?>");

        Blade::directive('endhaspermission', fn () => '<?php endif; ?>');

        Blade::directive('hasanypermission', fn ($expression) =>
            "<?php if (\\App\\Support\\Perm::hasAny({$expression})): ?>");

        Blade::directive('endhasanypermission', fn () => '<?php endif; ?>');

        Blade::directive('cangroup', fn ($expression) =>
            "<?php if (\\App\\Support\\Perm::group({$expression})): ?>");

        Blade::directive('endcangroup', fn () => '<?php endif; ?>');
    }
}
