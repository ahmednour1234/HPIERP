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
 * الزيارات المنفذة كملف xlsx منسّق.
 *
 * الصفوف تأتي مفلترة من الاستعلام، فالملف يطابق ما على الشاشة.
 */
class ExecutedVisitsExport implements
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
            'رقم الزيارة',
            'التاريخ',
            'العميل',
            'الموبايل',
            'نوع الجهة',
            'التخصص',
            'المنطقة',
            'المندوب',
            'الملاحظة',
            'خط العرض',
            'خط الطول',
        ];
    }

    public function title(): string
    {
        return 'الزيارات المنفذة';
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
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                $sheet->setRightToLeft(true);
                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->freezePane('A2');

                if ($lastRow < 2) {
                    return;
                }

                $sheet->getStyle('A1:K' . $lastRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'D9E2EC'],
                        ],
                    ],
                ]);

                $sheet->getStyle('A2:K' . $lastRow)->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // الملاحظات تصل لفقرات، فتُلَفّ بعرض ثابت بدل أن تمدّ
                // العمود بلا حد.
                $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setWrapText(true);
                $sheet->getColumnDimension('I')->setAutoSize(false);
                $sheet->getColumnDimension('I')->setWidth(55);

                $sheet->getStyle('C2:C' . $lastRow)->getAlignment()->setWrapText(true);
                $sheet->getColumnDimension('C')->setAutoSize(false);
                $sheet->getColumnDimension('C')->setWidth(32);

                foreach (['A', 'B', 'E', 'J', 'K'] as $column) {
                    $sheet->getStyle($column . '2:' . $column . $lastRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                for ($row = 2; $row <= $lastRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray([
                            'fill' => [
                                'fillType'   => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F4F8FB'],
                            ],
                        ]);
                    }
                }

                $sheet->setAutoFilter('A1:K' . $lastRow);
            },
        ];
    }
}
