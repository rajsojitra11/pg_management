<?php

namespace Modules\Report\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TenantReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
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
        return [
            'S.No', 'Tenant', 'Email', 'Phone', 'PG', 'Room No', 'Bed No', 'Date of Birth', 'Gender', 'Occupation', 'Address', 'Check-in Date', 'Expected Checkout', 'Monthly Rent', 'Security Deposit', 'Payment Method', 'ID Proof Type', 'ID Proof Number', 'Emergency Contact Name', 'Emergency Relation', 'Emergency Contact Number', 'Permanent State', 'Permanent City', 'Permanent Address', 'Additional Notes', 'Status',
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $row->name ?? '—',
            $row->email ?? '—',
            $row->phone ?? '—',
            $row->pg?->pg_name ?? '—',
            $row->room?->room_no ?? '—',
            $row->bed_no ?: '—',
            $row->date_of_birth ? Carbon::parse($row->date_of_birth)->format('d-m-Y') : '—',
            $row->gender ? ucfirst($row->gender) : '—',
            $row->occupation ?: '—',
            $row->address ?: '—',
            $row->checkin_date ? Carbon::parse($row->checkin_date)->format('d-m-Y') : '—',
            $row->expected_checkout_date ? Carbon::parse($row->expected_checkout_date)->format('d-m-Y') : '—',
            $row->monthly_rent !== null ? '₹'.number_format((float) $row->monthly_rent, 2) : '—',
            $row->security_deposit !== null ? '₹'.number_format((float) $row->security_deposit, 2) : '—',
            $row->payment_method ?: '—',
            $row->id_proof_type ?: '—',
            $row->id_proof_number ?: '—',
            $row->emergency_contact_name ?: '—',
            $row->emergency_relation ?: '—',
            $row->emergency_contact_number ?: '—',
            $row->permanentState?->name ?? '—',
            $row->permanentCity?->name ?? '—',
            $row->permanent_address ?: '—',
            $row->additional_notes ?: '—',
            ucfirst($row->status ?? '—'),
        ];
    }
}
