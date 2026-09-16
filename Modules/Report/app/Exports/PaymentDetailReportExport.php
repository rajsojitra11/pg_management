<?php

namespace Modules\Report\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentDetailReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    public function __construct(private Builder $query) {}

    public function collection(): Collection
    {
        $this->rowNumber = 0;

        return $this->query->orderBy('payment_date')->orderBy('id')->get();
    }

    public function headings(): array
    {
        return ['S.No', 'Room No', 'Tenant', 'Payment Date', 'Amount', 'Payment Method', 'Reference No', 'Status'];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $row->room?->room_no ?? '—',
            $row->tenant?->name ?? '—',
            $row->payment_date ? Carbon::parse($row->payment_date)->format('d-m-Y') : '—',
            '₹'.number_format((float) $row->amount, 2),
            $row->payment_method ?: '—',
            $row->reference_no ?: '—',
            ucfirst($row->verified ?? '—'),
        ];
    }
}
