<?php

namespace Modules\Report\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MaintenanceReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    public function __construct(private Builder $query) {}

    public function collection(): Collection
    {
        $this->rowNumber = 0;

        return $this->query->get();
    }

    public function headings(): array
    {
        return ['S.No', 'Maintenance No', 'Complaint No', 'Room No', 'Maintenance Date', 'Description', 'Cost', 'Status'];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $row->maintenance_no ?? '—',
            $row->complaint?->complaint_no ?? '—',
            $row->complaint?->room?->room_no ?? '—',
            $row->maintenance_date ? Carbon::parse($row->maintenance_date)->format('d-m-Y') : '—',
            $row->description ?? '—',
            $row->cost !== null ? '₹'.number_format((float) $row->cost, 2) : '—',
            ucfirst(str_replace('_', ' ', $row->status ?? '—')),
        ];
    }
}
