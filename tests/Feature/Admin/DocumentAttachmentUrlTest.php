<?php

namespace Tests\Feature\Admin;

use App\Models\DocumentAttachment;
use Tests\Feature\Api\ApiTestCase;

/**
 * روابط مرفقات الوثائق تحمل بادئة public.
 *
 * جذر الويب في الإنتاج هو مجلد المشروع لا public، فـ/storage/… يردّ
 * 302 بينما /public/storage/… يصل إلى الملف. كان المُلحِق يغفلها،
 * فلا يُفتح أي مرفق ويبدو زرّ «عرض» بلا أثر.
 */
class DocumentAttachmentUrlTest extends ApiTestCase
{
    private function srcOf(string $type, string $url): string
    {
        return (new DocumentAttachment(['type' => $type, 'url' => $url]))->src;
    }

    /** @test */
    public function a_stored_file_gets_the_public_prefix(): void
    {
        $this->assertStringContainsString(
            '/public/storage/documents/a.pdf',
            $this->srcOf('pdf', 'documents/a.pdf')
        );
    }

    /** ولا تُكتب البادئة مرتين على مسارٍ يحمل storage أصلًا. */
    public function test_a_storage_prefixed_path_is_not_doubled(): void
    {
        foreach (['storage/documents/a.pdf', '/storage/documents/a.pdf'] as $url) {
            $src = $this->srcOf('pdf', $url);

            $this->assertStringContainsString('/public/storage/documents/a.pdf', $src);
            $this->assertStringNotContainsString('storage/storage', $src);
            $this->assertStringNotContainsString('public/public', $src);
        }
    }

    /** الرابط الخارجي يُترك كما هو. */
    public function test_an_external_link_is_untouched(): void
    {
        $this->assertSame(
            'https://example.com/a.pdf',
            $this->srcOf('link', 'https://example.com/a.pdf')
        );
    }

    /** وقيمة فارغة لا تنتج رابطًا. */
    public function test_an_empty_url_yields_nothing(): void
    {
        $this->assertSame('', $this->srcOf('pdf', ''));
    }

    /**
     * القالب يقرأ من المُلحِق لا يبني الرابط بنفسه.
     *
     * بناؤه في مكانين يعني أن إصلاح أحدهما يترك الآخر مكسورًا — وهو
     * ما حدث: البطاقة كانت تعمل والنافذة لا.
     *
     * @test
     */
    public function the_view_does_not_build_the_url_itself(): void
    {
        $blade = file_get_contents(resource_path('views/admin-views/documents/show.blade.php'));

        $this->assertStringNotContainsString('asset($att->url)', $blade);
    }
}
