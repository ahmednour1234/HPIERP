<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * الصفحات العامة: سياسة الخصوصية والدعم.
 *
 * مطلوبة للنشر على متاجر التطبيقات، فهي خارج حارس تسجيل الدخول ويجب أن
 * تفتح لأي زائر. العربية هي الأصل والإنجليزية ترجمة، واللغة في الرابط
 * لا في الجلسة حتى يكون الرابط الواحد ثابت اللغة أينما أُرسل.
 */
class PublicPageController extends Controller
{
    private const LOCALES = ['ar', 'en'];

    public function privacy(string $locale = 'ar'): View|RedirectResponse
    {
        return $this->page('privacy', $locale);
    }

    public function support(string $locale = 'ar'): View|RedirectResponse
    {
        return $this->page('support', $locale);
    }

    private function page(string $page, string $locale): View|RedirectResponse
    {
        // لغة غير معروفة تُحوَّل إلى العربية بدل 404: الرابط قد يُكتب يدويًّا.
        if (!in_array($locale, self::LOCALES, true)) {
            return redirect()->route("public.{$page}", ['locale' => 'ar']);
        }

        return view("public.{$page}", [
            'locale'  => $locale,
            'dir'     => $locale === 'ar' ? 'rtl' : 'ltr',
            'contact' => $this->contact(),
            'updated' => $this->lastUpdated(),
        ]);
    }

    /**
     * بيانات التواصل من إعدادات المتجر، وإلا القيم الافتراضية.
     *
     * الإعدادات فارغة على نسخ كثيرة، وصفحة دعم بلا وسيلة تواصل لا تفيد.
     */
    private function contact(): array
    {
        // صفحة ثابتة لا يصحّ أن تسقط لأن جدول الإعدادات غائب أو تعذّر
        // الاتصال بقاعدة البيانات؛ تُعرض القيم الافتراضية حينئذٍ.
        $setting = function (string $key, string $fallback): string {
            try {
                $value = DB::table('business_settings')->where('type', $key)->value('value');
            } catch (\Throwable) {
                return $fallback;
            }

            return trim((string) ($value ?: '')) ?: $fallback;
        };

        return [
            'company' => $setting('shop_name', 'Tayer'),
            'email'   => $setting('shop_email', 'info@hpi-eg.com'),
            'phone'   => $setting('shop_phone', ''),
            'address' => $setting('shop_address', ''),
        ];
    }

    /** تاريخ آخر تحديث، ثابت حتى يُحدَّث النصّ نفسه. */
    private function lastUpdated(): string
    {
        return '2026-09-26';
    }
}
