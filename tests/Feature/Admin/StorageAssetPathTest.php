<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * روابط الملفات المرفوعة تحمل بادئة public.
 *
 * جذر الويب في الإنتاج هو مجلد المشروع لا public، ولهذا تكتب القوالب
 * asset('public/assets/...') صراحةً. نداء asset('storage/...') بلا
 * البادئة يعيد 302 بدل الصورة، وهو عطل صامت: الصفحة تفتح والصور وحدها
 * لا تظهر، فلا يكشفه اختبار حالة الاستجابة.
 */
class StorageAssetPathTest extends TestCase
{
    /** @test */
    public function no_view_links_storage_without_the_public_prefix(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $file) {
            $body = file_get_contents($file);

            // asset('storage/...) أو asset("storage/...) بلا public قبلها
            if (preg_match('#asset\(\s*[\'"]storage/#', $body)) {
                $offenders[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "استخدم asset('public/storage/...') أو الدالة storage_url():\n" . implode("\n", $offenders)
        );
    }

    /** @test */
    public function the_helper_builds_a_public_prefixed_url(): void
    {
        $this->assertStringContainsString(
            'public/storage/shop/a.jpg',
            \App\CPU\storage_url('shop/a.jpg')
        );

        // الشرطة البادئة لا تُنتج خطًّا مزدوجًا.
        $this->assertStringContainsString(
            'public/storage/shop/a.jpg',
            \App\CPU\storage_url('/shop/a.jpg')
        );
    }

    /** @test */
    public function the_helper_passes_through_absolute_urls_and_blanks(): void
    {
        $this->assertNull(\App\CPU\storage_url(''));
        $this->assertNull(\App\CPU\storage_url('   '));
        $this->assertSame(
            'https://cdn.example.com/a.jpg',
            \App\CPU\storage_url('https://cdn.example.com/a.jpg')
        );
    }

    private function bladeFiles(): array
    {
        $files = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($it as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
