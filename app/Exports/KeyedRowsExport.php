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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * صفوف مفتاحية إلى xlsx منسّق.
 *
 * العناوين تُشتق من مفاتيح أول صف، فتصلح لأي تقرير يبني صفوفه
 * ['العنوان' => القيمة] دون كلاس تصدير خاص به.
 */
class KeyedRowsExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithTitle,
    WithEvents,
    ShouldAutoSize
{
    private array $columns;

    public function __construct(private Collection $rows, private string $sheetTitle = 'تقرير')
    {
        $this->columns = $rows->isNotEmpty() ? array_keys((array) $rows->first()) : [];
    }

    public function collection(): Collection
    {
        // القيم وحدها بالترتيب؛ المفاتيح صارت عناوين.
        return $this->rows->map(fn ($row) => array_values((array) $row))->values();
    }

    public function headings(): array
    {
        return $this->columns ?: ['لا توجد بيانات'];
    }

    public function title(): string
    {
        // Excel يرفض هذه المحارف في اسم الورقة ويحدّها بـ 31 حرفًا.
        return mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $this->sheetTitle), 0, 31);
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
                $lastCol = Coordinate::stringFromColumnIndex(max(1, count($this->columns)));
                $range   = 'A1:' . $lastCol . $lastRow;

                $sheet->setRightToLeft(true);
                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->freezePane('A2');

                if ($lastRow < 2) {
                    return;
                }

                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'D9E2EC'],
                        ],
                    ],
                ]);

                $sheet->getStyle('A2:' . $lastCol . $lastRow)->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                for ($row = 2; $row <= $lastRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray([
                            'fill' => [
                                'fillType'   => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F4F8FB'],
                            ],
                        ]);
                    }
                }

                $sheet->setAutoFilter($range);
            },
        ];
    }
}
