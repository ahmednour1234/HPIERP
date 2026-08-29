<?php

namespace App\Providers;
ini_set('memory_limit', '-1');
use Illuminate\Pagination\Paginator;
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
}
