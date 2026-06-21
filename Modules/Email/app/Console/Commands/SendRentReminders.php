<?php

namespace Modules\Email\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Modules\Email\Mail\RentReminderMail;
use Modules\Email\Models\EmailConfig;
use Modules\Email\Models\EmailTemplate;
use Modules\Tenant\Models\Tenant;

class SendRentReminders extends Command
{
    protected $signature = 'email:send-rent-reminders {--dry-run : Preview without sending}';

    protected $description = 'Send rent reminder emails 2 days before tenant check-in date each month';

    public function handle(): int
    {
        $targetDay = now()->addDays(2)->day;

        $activeConfigs = EmailConfig::where('status', 'active')->pluck('pg_id');

        if ($activeConfigs->isEmpty()) {
            $this->warn('No active email configurations found.');

            return Command::SUCCESS;
        }

        $tenants = Tenant::with('pg', 'room')
            ->whereIn('pg_id', $activeConfigs)
            ->whereRaw('DAY(checkin_date) = ?', [$targetDay])
            ->where('status', 'active')
            ->get();

        if ($tenants->isEmpty()) {
            $this->info('No tenants found for rent reminder today.');

            return Command::SUCCESS;
        }

        $template = EmailTemplate::where('name', 'rent_reminder')->where('status', 'active')->first();
        if (! $template) {
            $this->error('Default rent_reminder email template not found or inactive.');

            return Command::FAILURE;
        }

        $configs = EmailConfig::whereIn('pg_id', $tenants->pluck('pg_id')->unique())
            ->where('status', 'active')
            ->get()
            ->keyBy('pg_id');

        $sent = 0;
        $failed = 0;

        foreach ($tenants as $tenant) {
            $config = $configs->get($tenant->pg_id);

            if (! $config) {
                continue;
            }

            if (! $tenant->email) {
                $this->warn("Tenant {$tenant->name} has no email. Skipping.");

                continue;
            }

            $subjectPrefix = $config->subject_prefix ? "[{$config->subject_prefix}] " : '';
            $subject = $subjectPrefix.$template->subject;
            $dueDate = now()->addDays(2)->format('d-m-Y');
            $currentMonth = now()->format('F Y');

            $placeholders = [
                '{due_date}' => $dueDate,
                '{current_month}' => $currentMonth,
                '{sender_name}' => $config->sender_name ?? '',
            ];

            $mailable = (new RentReminderMail($tenant, $subject, $template->body, $placeholders))
                ->from($config->sender_email, $config->sender_name ?? $config->sender_email);

            if ($this->option('dry-run')) {
                $this->line("[DRY-RUN] Would send to {$tenant->email} from {$config->sender_email} (PG: {$tenant->pg?->pg_name})");
                $sent++;
            } else {
                try {
                    Mail::mailer('smtp')->to($tenant->email)->send($mailable);
                    $sent++;
                    $this->line("Sent reminder to {$tenant->name} <{$tenant->email}> from {$config->sender_email} (PG: {$tenant->pg?->pg_name})");
                } catch (\Exception $e) {
                    $failed++;
                    $this->error("Failed to send to {$tenant->email}: {$e->getMessage()}");
                }
            }
        }

        $this->info("Done. Sent: {$sent}, Failed: {$failed}");

        return Command::SUCCESS;
    }
}
