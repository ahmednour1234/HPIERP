<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * الزيارات كملف xlsx منسّق.
 *
 * الصفوف تأتي جاهزة من الاستعلام المفلتر، فالملف يطابق ما على الشاشة.
 */
class VisitsExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithTitle,
    WithEvents,
    ShouldAutoSize
{
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
            '#',
            'المندوب',
            'العميل',
            'المنطقة',
            'تاريخ الزيارة',
            'ملاحظة',
        ];
    }

    public function title(): string
    {
        return 'الزيارات';
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

                $sheet->getStyle('A1:F' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'D9E2EC'],
                        ],
                    ],
                ]);

                $sheet->getStyle('A2:F' . $lastRow)->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // اسم العميل والملاحظة يطولان، فيُلَفّان بعرض ثابت بدل
                // أن يمدّا العمود بلا حد.
                foreach (['C' => 42, 'F' => 38] as $column => $width) {
                    $sheet->getStyle($column . '2:' . $column . $lastRow)
                        ->getAlignment()->setWrapText(true);
                    $sheet->getColumnDimension($column)->setAutoSize(false);
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                foreach (['A', 'E'] as $column) {
                    $sheet->getStyle($column . '2:' . $column . $lastRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // تظليل متبادل ليسهل تتبّع السطر الطويل بالعين.
                for ($row = 2; $row <= $lastRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                            'fill' => [
                                'fillType'   => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F4F8FB'],
                            ],
                        ]);
                    }
                }

                $sheet->setAutoFilter('A1:F' . $lastRow);
            },
        ];
    }
}
