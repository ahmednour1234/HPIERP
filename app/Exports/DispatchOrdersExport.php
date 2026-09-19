<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * أوامر الصرف كملف xlsx منسّق.
 *
 * الصفوف جاهزة من الاستعلام: التصدير يعرض ما تعرضه الشاشة بالفلاتر
 * نفسها، فلا يبني استعلامه الخاص حتى لا يختلف عنها.
 */
class DispatchOrdersExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithTitle,
    WithEvents,
    WithColumnFormatting,
    ShouldAutoSize
{
    public function __construct(
        private Collection $rows,
        private ?string $sellerName = null,
        private ?string $fromDate = null,
        private ?string $toDate = null
    ) {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'رقم الأمر',
            'المندوب',
            'كود المندوب',
            'العميل',
            'عدد الأصناف',
            'الأصناف',
            'الإجمالي',
            'ملاحظات المندوب',
            'التاريخ',
        ];
    }

    public function title(): string
    {
        return 'أوامر الصرف';
    }

    public function columnFormats(): array
    {
        return [
            // الإجمالي مبلغ: بدون تنسيق يظهر 7200 بلا فاصلة ولا كسور.
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '14395C'],
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
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                // الورقة عربية، فتُقرأ من اليمين.
                $sheet->setRightToLeft(true);

                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->freezePane('A2');

                if ($lastRow < 2) {
                    return;
                }

                $range = 'A1:I' . $lastRow;

                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'D9E2EC'],
                        ],
                    ],
                ]);

                $sheet->getStyle('A2:I' . $lastRow)->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // الأصناف قد تطول، فتُلَف بدل أن تمتد بلا نهاية.
                $sheet->getStyle('F2:F' . $lastRow)->getAlignment()->setWrapText(true);
                $sheet->getColumnDimension('F')->setAutoSize(false);
                $sheet->getColumnDimension('F')->setWidth(45);

                foreach (['A', 'C', 'E', 'G', 'I'] as $column) {
                    $sheet->getStyle($column . '2:' . $column . $lastRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // تظليل الصفوف الزوجية ليسهل تتبّع السطر الطويل بالعين.
                for ($row = 2; $row <= $lastRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                            'fill' => [
                                'fillType'   => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F4F8FB'],
                            ],
                        ]);
                    }
                }

                $sheet->setAutoFilter('A1:I' . $lastRow);
            },
        ];
    }
}
