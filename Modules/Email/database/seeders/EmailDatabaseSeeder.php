<?php

namespace Modules\Email\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmailDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $defaultDate = getDefaultMigrationDate();

        $existingTemplate = DB::table('email_templates')->where('name', 'rent_reminder')->first();

        if (! $existingTemplate) {
            DB::table('email_templates')->insert([
                'public_id' => Str::ulid(),
                'name' => 'rent_reminder',
                'subject' => 'Rent Reminder - {current_month}',
                'body' => '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rent Reminder</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 24px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #18181b 0%, #27272a 100%); padding: 28px 24px; text-align: center;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding-bottom: 12px;">
                                        <span style="display: inline-block; width: 56px; height: 56px; background-color: #ffffff; border-radius: 50%; text-align: center; line-height: 56px; font-size: 28px;">🏠</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: 0.5px;">RENT REMINDER</h1>
                                        <p style="color: #a1a1aa; margin: 6px 0 0 0; font-size: 13px;">Monthly Rent Notice</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px 32px 24px;">
                            <p style="font-size: 16px; color: #18181b; margin: 0 0 16px 0;">Dear <strong style="color: #09090b;">{tenant_name}</strong>,</p>
                            <p style="font-size: 14px; color: #52525b; line-height: 1.7; margin: 0 0 20px 0;">
                                This is a friendly reminder that your rent for the month of <strong style="color: #18181b;">{current_month}</strong> is due on <strong style="color: #dc2626;">{due_date}</strong>. Please find the details below:
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #e4e4e7; border-radius: 8px; overflow: hidden;">
                                <tr style="background-color: #fafafa;">
                                    <td style="padding: 14px 18px; border-bottom: 1px solid #e4e4e7; width: 45%;">
                                        <p style="font-size: 12px; color: #71717a; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">Property</p>
                                    </td>
                                    <td style="padding: 14px 18px; border-bottom: 1px solid #e4e4e7;">
                                        <p style="font-size: 14px; color: #18181b; margin: 0; font-weight: 600;">{pg_name}</p>
                                    </td>
                                </tr>
                                <tr style="background-color: #ffffff;">
                                    <td style="padding: 14px 18px; border-bottom: 1px solid #e4e4e7;">
                                        <p style="font-size: 12px; color: #71717a; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">Room No</p>
                                    </td>
                                    <td style="padding: 14px 18px; border-bottom: 1px solid #e4e4e7;">
                                        <p style="font-size: 14px; color: #18181b; margin: 0; font-weight: 600;">{room_no}</p>
                                    </td>
                                </tr>
                                <tr style="background-color: #fafafa;">
                                    <td style="padding: 14px 18px; border-bottom: 1px solid #e4e4e7;">
                                        <p style="font-size: 12px; color: #71717a; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">Monthly Rent</p>
                                    </td>
                                    <td style="padding: 14px 18px; border-bottom: 1px solid #e4e4e7;">
                                        <p style="font-size: 16px; color: #18181b; margin: 0; font-weight: 700;">₹{monthly_rent}</p>
                                    </td>
                                </tr>
                                <tr style="background-color: #ffffff;">
                                    <td style="padding: 14px 18px;">
                                        <p style="font-size: 12px; color: #71717a; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">Due Date</p>
                                    </td>
                                    <td style="padding: 14px 18px;">
                                        <p style="font-size: 14px; color: #dc2626; margin: 0; font-weight: 700;">{due_date}</p>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 20px 0 0; background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 14px 18px;">
                                        <p style="font-size: 13px; color: #991b1b; margin: 0; line-height: 1.5;">
                                            <strong>⚠ Note:</strong> A late fee may be applied for payments received after the due date. Please pay on time.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 24px 0 0;">
                                <tr>
                                    <td align="center">
                                        <p style="font-size: 14px; color: #52525b; line-height: 1.7; margin: 0 0 4px;">Thank you for your cooperation.</p>
                                        <p style="font-size: 14px; color: #52525b; line-height: 1.7; margin: 0;">Regards,<br><strong style="color: #18181b;">{sender_name}</strong></p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #fafafa; border-top: 1px solid #e4e4e7; padding: 20px 32px; text-align: center;">
                            <p style="font-size: 12px; color: #a1a1aa; margin: 0 0 4px;">This is an automated reminder from <strong style="color: #71717a;">{pg_name}</strong></p>
                            <p style="font-size: 11px; color: #a1a1aa; margin: 0;">For assistance, please contact the property management office.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>',
                'placeholders' => '{tenant_name}, {tenant_email}, {pg_name}, {room_no}, {checkin_date}, {monthly_rent}, {due_date}, {current_month}, {sender_name}',
                'is_default' => true,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $defaultDate,
                'updated_at' => $defaultDate,
            ]);
        }
    }
}
