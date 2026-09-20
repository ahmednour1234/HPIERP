<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * أسماء مستعارة في selectRaw لا تصطدم بالكلمات المحجوزة.
 *
 * قاعدة التطوير هنا SQLite وقاعدة الإنتاج MariaDB، وSQLite تقبل
 * أسماء ترفضها MariaDB. «lines» مثالٌ حيّ: مرّت الاختبارات وسقطت
 * صفحة مخزون العربيات في الإنتاج بخطأ 1064.
 */
class SqlAliasTest extends TestCase
{
    /**
     * كلمات محجوزة في MariaDB/MySQL تظهر عادةً كأسماء مستعارة.
     *
     * ليست القائمة كاملة — وهي لا تحتاج أن تكون — بل تغطّي ما يُشتقّ
     * من أسماء الأعمدة في هذا المشروع.
     */
    private const RESERVED = [
        'lines', 'order', 'group', 'key', 'range', 'rank', 'read',
        'write', 'interval', 'condition', 'match', 'partition',
        'usage', 'option', 'system', 'accessible', 'analyze',
        'asensitive', 'before', 'cascade', 'describe', 'dual',
        'escape', 'exit', 'explain', 'float4', 'force', 'general',
        'high_priority', 'ignore', 'index', 'infile', 'leading',
        'leave', 'limit', 'linear', 'load', 'lock', 'long', 'loop',
        'maxvalue', 'mod', 'natural', 'optimize', 'outfile', 'purge',
        'release', 'rename', 'require', 'resignal', 'restrict',
        'schema', 'separator', 'signal', 'spatial', 'sql_big_result',
        'ssl', 'starting', 'straight_join', 'terminated', 'undo',
        'unlock', 'xor', 'zerofill',
    ];

    public function test_no_select_alias_uses_a_reserved_word(): void
    {
        $offenders = [];

        foreach ($this->phpFiles() as $file) {
            $code = file_get_contents($file);

            // داخل استدعاءات SQL وحدها: البحث في الملف كلّه يلتقط
            // تعليقات إنجليزية مثل "Mark the notification as read".
            preg_match_all(
                '/(?:selectRaw|whereRaw|havingRaw|orderByRaw|raw|from)\s*\(\s*([\'"])(.*?)\1/is',
                $code,
                $calls
            );

            $sql = implode(' ', $calls[2] ?? []);

            preg_match_all('/\bas\s+([a-z_][a-z0-9_]*)\b/i', $sql, $matches);

            foreach ($matches[1] as $alias) {
                if (in_array(strtolower($alias), self::RESERVED, true)) {
                    $offenders[] = basename($file) . ' → ' . $alias;
                }
            }
        }

        $this->assertSame([], array_unique($offenders),
            'اسم مستعار يصطدم بكلمة محجوزة في MariaDB');
    }

    /** @return iterable<string> */
    private function phpFiles(): iterable
    {
        foreach (['app/Http/Controllers', 'app/Services', 'app/Repositories'] as $dir) {
            $path = base_path($dir);

            if (! is_dir($path)) {
                continue;
            }

            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));

            foreach ($it as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    yield $file->getPathname();
                }
            }
        }
    }
}
