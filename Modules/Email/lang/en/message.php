<?php

return [
    'module_name' => 'Email',
    'title' => 'Email & Rent Reminder Settings',
    'config_tab' => 'Email Configurations',
    'template_tab' => 'Email Template',
    'config_title' => 'PG Email Configurations',
    'template_title' => 'Rent Reminder Email Template',

    'pg' => 'PG',
    'sender_email' => 'Sender Email',
    'sender_name' => 'Sender Name',
    'subject_prefix' => 'Subject Prefix',
    'status' => 'Status',

    'add_config' => 'Add Email Config',
    'edit_config' => 'Edit Email Config',

    'config_created' => 'Email configuration created successfully.',
    'config_updated' => 'Email configuration updated successfully.',
    'config_deleted' => 'Email configuration deleted successfully.',

    'template_subject' => 'Subject',
    'template_body' => 'Email Body (HTML)',
    'template_placeholders' => 'Available Placeholders',
    'template_saved' => 'Email template saved successfully.',

    'placeholders_info' => 'Use these placeholders in your subject and body:',
    'placeholder_tenant_name' => 'Tenant Name',
    'placeholder_tenant_email' => 'Tenant Email',
    'placeholder_pg_name' => 'PG Name',
    'placeholder_room_no' => 'Room No',
    'placeholder_checkin_date' => 'Check-in Date',
    'placeholder_monthly_rent' => 'Monthly Rent',
    'placeholder_due_date' => 'Due Date (2 days before check-in)',
    'placeholder_current_month' => 'Current Month',
    'placeholder_sender_name' => 'Sender Name',

    'preview' => 'Preview',
    'previewing' => 'Previewing...',
    'preview_required' => 'Please enter both subject and body to preview.',

    'send_test' => 'Send Test Email',
    'test_email_sent' => 'Test email sent successfully.',
    'test_email_failed' => 'Failed to send test email.',

    'validation' => [
        'pg_required' => 'Please select a PG.',
        'email_required' => 'Please enter the sender email.',
        'email_invalid' => 'Please enter a valid email address.',
        'subject_required' => 'Please enter the email subject.',
        'body_required' => 'Please enter the email body.',
    ],

    'placeholder' => [
        'select_pg' => 'Select PG',
        'enter_email' => 'Enter sender email',
        'enter_name' => 'Enter sender name',
        'enter_prefix' => 'Enter subject prefix (optional)',
        'enter_subject' => 'Enter email subject',
        'enter_body' => 'Write your email template here with HTML...',
    ],
];
