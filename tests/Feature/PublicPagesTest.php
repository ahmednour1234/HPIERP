<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * الصفحتان العامتان تفتحان بلا تسجيل دخول.
 *
 * متاجر التطبيقات تفتح رابط سياسة الخصوصية بلا حساب، فحارس الدخول أو
 * الـfallback الذي يحوّل كل مسار غير معروف إلى صفحة الدخول يُسقط النشر.
 */
class PublicPagesTest extends TestCase
{
    /** @return array<string, array{string}> */
    public function publicUrls(): array
    {
        return [
            'privacy default' => ['/privacy-policy'],
            'privacy arabic'  => ['/privacy-policy/ar'],
            'privacy english' => ['/privacy-policy/en'],
            'support default' => ['/support'],
            'support arabic'  => ['/support/ar'],
            'support english' => ['/support/en'],
        ];
    }

    /** @dataProvider publicUrls */
    public function test_it_opens_without_signing_in(string $url): void
    {
        $this->get($url)->assertOk();
    }

    public function test_it_is_not_swallowed_by_the_login_fallback(): void
    {
        // المسار غير المعروف يُحوَّل إلى الدخول؛ الصفحتان يجب ألّا تكونا كذلك.
        $this->get('/no-such-page')->assertRedirect('admin/auth/login');

        $this->get('/privacy-policy')->assertOk()->assertSee('privacy', false);
    }

    public function test_each_locale_sets_its_own_language_and_direction(): void
    {
        $this->get('/privacy-policy/ar')->assertOk()
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false);

        $this->get('/privacy-policy/en')->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSee('dir="ltr"', false);
    }

    /** لغة غير معروفة تعود إلى العربية بدل 404. */
    public function test_an_unknown_locale_falls_back_to_arabic(): void
    {
        $this->get('/support/fr')->assertRedirect(route('public.support', ['locale' => 'ar']));
    }

    /** كل صفحة تشير إلى الأخرى، فلا تكون طريقًا مسدودًا. */
    public function test_the_pages_link_to_each_other(): void
    {
        $this->get('/privacy-policy/ar')->assertOk()->assertSee('/support/ar', false);
        $this->get('/support/ar')->assertOk()->assertSee('/privacy-policy/ar', false);
    }
}
