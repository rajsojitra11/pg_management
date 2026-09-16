<?php

namespace Modules\Report\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ComplaintReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
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
        return ['S.No', 'Complaint No', 'Room No', 'Category', 'Service', 'Complaint Date', 'Note', 'Status'];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $row->complaint_no ?? '—',
            $row->room?->room_no ?? '—',
            $row->category?->service_category_name ?? '—',
            $row->service?->service_name ?? '—',
            $row->complaint_date ? Carbon::parse($row->complaint_date)->format('d-m-Y') : '—',
            $row->note ?? '—',
            ucfirst(str_replace('_', ' ', $row->status ?? '—')),
        ];
    }
}
