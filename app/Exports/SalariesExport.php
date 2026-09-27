<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * الرواتب كملف xlsx منسّق.
 *
 * الصفوف تأتي مفلترة من المتحكّم، فالملف يطابق ما على الشاشة، ويحمل كل
 * النتائج لا صفحة الترقيم وحدها.
 */
class SalariesExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithTitle,
    // بدونها تُكتب القيمة 0 خانةً فارغة، وفي كشف رواتب تُقرأ الخانة
    // الفارغة بيانًا ناقصًا لا مبلغًا يساوي صفرًا.
    WithStrictNullComparison,
    WithEvents,
    ShouldAutoSize
{
    /** الأعمدة الرقمية، لتنسيقها كأرقام لا كنصوص. */
    private const MONEY = ['C', 'D', 'J', 'K', 'L', 'M', 'O'];
    private const COUNTS = ['A', 'G', 'H', 'I', 'N'];

    public function __construct(private Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'معرف البائع',
            'اسم البائع',
            'الراتب',
            'إجمالي التحصيلات',
            'ملاحظة',
            'ملاحظة المدير',
            'عدد أيام العمل',
            'عدد الزيارات',
            'نتيجة الزيارات',
            'مكافأة الالتزام',
            'حافز البيع',
            'بدلات أخرى',
            'الخصم',
            'النقاط %',
            'المجموع',
            'الشهر',
        ];
    }

    public function title(): string
    {
        return 'الرواتب';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '11245A'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                $sheet->setRightToLeft(true);
                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->freezePane('A2');

                if ($lastRow < 2) {
                    return;
                }

                // الترشيح من الترويسة: الملف يُراجَع عادةً بفرز وتصفية.
                $sheet->setAutoFilter('A1:P' . $lastRow);

                $sheet->getStyle('A1:P' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'D9E2EC'],
                        ],
                    ],
                ]);

                $sheet->getStyle('A2:P' . $lastRow)->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // المبالغ أرقامًا بفواصل آلاف، لا نصًّا: العمود مخزَّن نصًّا
                // في قاعدة البيانات فيصل إلى إكسل بلا تنسيق ولا يُجمَع.
                // الصفر يُكتب 0.00 لا خانة فارغة: في كشف رواتب تبدو
                // الخانة الفارغة بيانًا ناقصًا لا مبلغًا يساوي صفرًا.
                foreach (self::MONEY as $column) {
                    $sheet->getStyle($column . '2:' . $column . $lastRow)
                        ->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00;"0.00"');
                }

                foreach (self::COUNTS as $column) {
                    $sheet->getStyle($column . '2:' . $column . $lastRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // المجموع هو ما يُقرأ أولًا.
                $sheet->getStyle('O2:O' . $lastRow)->getFont()->setBold(true);

                // الملاحظات تصل لفقرات، فتُلَفّ بعرض ثابت.
                foreach (['E', 'F'] as $column) {
                    $sheet->getStyle($column . '2:' . $column . $lastRow)
                        ->getAlignment()->setWrapText(true);
                    $sheet->getColumnDimension($column)->setAutoSize(false);
                    $sheet->getColumnDimension($column)->setWidth(38);
                }
            },
        ];
    }
}
