<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * تصدير جداول الشاشات إلى ملف يفتحه Excel مباشرة.
 *
 * maatwebsite/excel غير مثبّت في المشروع، وExcel يفتح CSV بلا وسيط، فنكتفي
 * به مع BOM حتى تظهر العربية سليمة بدل رموز مشوّهة. كانت هذه الدالة مكرّرة
 * في أكثر من متحكّم، فجُمعت هنا ليكون سلوك كل التصديرات واحدًا.
 */
trait ExportsCsv
{
    /**
     * @param iterable $rows صفوف مصفوفات ترابطية؛ مفاتيح أول صف هي الترويسة.
     */
    protected function streamCsvRows($rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            // BOM: بدونه يقرأ Excel الملف بترميز النظام فتتلف العربية.
            fwrite($out, "\xEF\xBB\xBF");

            $wroteHeader = false;

            foreach ($rows as $row) {
                $row = (array) $row;

                if (!$wroteHeader) {
                    fputcsv($out, array_keys($row));
                    $wroteHeader = true;
                }

                fputcsv($out, array_values($row));
            }

            if (!$wroteHeader) {
                fputcsv($out, ['لا توجد بيانات']);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /** اسم ملف مؤرَّخ، حتى لا تتراكم ملفات بنفس الاسم في مجلد التنزيلات. */
    protected function exportFilename(string $prefix): string
    {
        return $prefix . '-' . now()->format('Y-m-d') . '.csv';
    }

    /**
     * قيم فلتر متعدد الاختيار، منظَّفة من الفراغات.
     *
     * الفلاتر المتعددة تصل مصفوفةً (name="x[]")، وقد تصل قيمة مفردة من رابط
     * قديم أو من صفحة أخرى، فنقبل الحالتين حتى لا تنكسر الروابط المحفوظة.
     */
    protected function multiFilter(Request $request, string $key): array
    {
        return array_values(array_filter(
            (array) $request->input($key, []),
            fn ($v) => $v !== '' && $v !== null
        ));
    }

    /** نفس القيم كنصوص، لإعادة تحديد الخيارات في القائمة. */
    protected function multiFilterStrings(Request $request, string $key): array
    {
        return array_map('strval', $this->multiFilter($request, $key));
    }
}
