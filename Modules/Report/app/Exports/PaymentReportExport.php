<?php

namespace Modules\Report\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    private array $monthColumns;

    private array $monthlyTotals;

    public function __construct(private Builder $query, private Collection $monthlyRows)
    {
        $this->monthColumns = $this->buildMonthColumns();
        $this->monthlyTotals = $this->buildMonthlyTotals();
    }

    public function collection(): Collection
    {
        $this->rowNumber = 0;

        return $this->query->get();
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Room No',
            'Tenant',
            'Payments',
            'Total Amount',
            ...array_column($this->monthColumns, 'label'),
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        $key = $row->room_id.':'.$row->tenant_id;

        $monthly = array_map(
            fn (array $col) => number_format($this->monthlyTotals[$key][$col['key']] ?? 0, 2),
            $this->monthColumns
        );

        return [
            $this->rowNumber,
            $row->room?->room_no ?? '—',
            $row->tenant?->name ?? '—',
            (int) $row->payment_count,
            '₹'.number_format((float) $row->total_amount, 2),
            ...$monthly,
        ];
    }

    private function buildMonthColumns(): array
    {
        $columns = [];
        $start = Carbon::now()->startOfMonth()->subMonths(11);

        for ($i = 0; $i < 12; $i++) {
            $date = $start->copy()->addMonths($i);
            $columns[] = [
                'label' => $date->format('M-y'),
                'key' => $date->format('Y-m'),
            ];
        }

        return $columns;
    }

    private function buildMonthlyTotals(): array
    {
        $totals = [];

        foreach ($this->monthlyRows as $row) {
            $totals[$row->room_id.':'.$row->tenant_id][$row->month] = (float) $row->month_amount;
        }

        return $totals;
    }
}
