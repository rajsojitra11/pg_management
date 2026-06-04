<?php

return [
    // Languages
    'English' => 'English',
    'Hindi' => 'Hindi',
    'Gujarati' => 'Gujarati',
    'Language' => 'Language',
    'dashboard' => 'Dashboard',

    // General UI Elements
    'loading' => 'Loading',
    'no_records_found' => 'No records found',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'reason_required_min_3' => 'Reason is required (minimum 3 characters)',
    'user_remark_required_min_3' => 'Please explain the reason for this change (minimum 3 characters)',
    'confirm_delete_item' => 'Are you sure you want to delete',
    'warning_permanent_action' => 'Warning: This action cannot be undone!',
    'user_remark_mandatory' => 'User Remark Required:',
    'explain_changes_requirement' => 'You must explain what changes you are making and why.',
    'deletion_reason_mandatory' => 'Deletion Reason Required:',
    'explain_deletion_requirement' => 'You must provide a detailed explanation for why this record is being deleted.',

    // Common nested array for frequently used items
    'common' => [
        // Actions
        'action' => 'Action',
        'actions' => 'Actions',
        'add' => 'Add',
        'addNew' => 'Add New',
        'edit' => 'Edit',
        'view' => 'View',
        'delete' => 'Delete',
        'deleting' => 'Deleting...',
        'delete_success' => 'Record deleted successfully',
        'delete_error' => 'Error deleting record',
        'validation_error' => 'Please check the form for errors',
        'save' => 'Save',
        'submit' => 'Submit',
        'cancel' => 'Cancel',
        'update' => 'Update',
        'back' => 'Back',
        'add_more' => 'Add More',
        'select' => '-- Select --',
        'priority' => 'Priority',
        'enter_priority' => 'Enter Priority',
        'warning' => 'Warning: This action cannot be undone!',
        'delete_warning' => 'Are you sure you want to delete this environment variable? This action cannot be undone.',
        'record_details' => 'Record Information',

        // Financial terms
        'total' => 'Total',
        'sub_total' => 'Sub Total',
        'grand_total' => 'Grand Total',
        'taxable' => 'Taxable Amt.',
        'gst' => 'GST',
        'gst_amount' => 'GST Amt.',
        'igst' => 'IGST',
        'ugst' => 'UGST',
        'sgst' => 'SGST',
        'cgst' => 'CGST',
        'round_off' => 'Round Off',

        // Status and general
        'yes' => 'Yes',
        'no' => 'No',
        'active' => 'Active',
        'inactive' => 'InActive',
        'all' => 'All',
        'status' => 'Status',
        'items' => 'Items',
        'no_data_found' => 'No Data Found',

        // Date and time
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'created_by' => 'Created By',
        'updated_by' => 'Updated By',

        // Geography
        'country' => 'Country',
        'state' => 'State',
        'city' => 'City',
        'select_country' => 'Select country',
        'select_state' => 'Select state',
        'select_city' => 'Select city',
        'select_currency' => 'Select currency',

        // Validation
        'enter_10_digits' => 'Enter at least 10 digits',
        'enter_6_digits' => 'Enter at least 6 digits',
        'enter_valid_number' => 'Enter valid number',

        // UI Messages
        'tentative' => 'Tentative, May change upon save.',
        'saving' => 'Saving...',
        'updating' => 'Updating...',
        'encrypted' => 'Encrypted',
        'no_activity_logs' => 'No activity logs found',
        'no_value_set' => 'No value set',

        // User remarks and entries
        'user_remark' => 'User Remark',
        'deletion_reason' => 'Deletion Reason',
        'confirm_delete' => 'Confirm Delete',
        'user_remark_help_create' => 'You can provide additional context for this entry',
        'user_remark_required_update' => 'Please provide a reason for this update',
        'user_remark_required_delete' => 'Please provide a reason for deletion',
        'user_remark_help_custom' => 'Please provide your remarks',

        // Operation results
        'error_occurred' => 'An error occurred. Please try again.',
        'success' => 'Operation completed successfully',
        'failed' => 'Operation failed',
        'invalid_request' => 'Invalid request',
        'unauthorized' => 'Unauthorized access',
        'forbidden' => 'Access forbidden',
        'not_found' => 'Resource not found',
        'validation_failed' => 'Validation failed',
        'save_failed' => 'Failed to save data',
        'delete_failed' => 'Failed to delete data',
        'update_failed' => 'Failed to update data',

        // Modal titles and labels
        'close' => 'Close',
        'confirm' => 'Confirm',
        'note' => 'Note:',
        'print' => 'Print Label',
        'print_label' => 'Print Label',
        'print_label_info' => 'The label will open in a new tab. This page will refresh automatically.',
        'confirm_status_change' => 'Confirm Status Change',
        'status_change_warning' => 'Please provide a reason for this status change.',
        'allow_reprint' => 'Allow Reprint',
        'allow_reprint_confirm' => 'Yes, Allow it!',
        'allow_reprint_warning' => "You won't be able to revert this! Please provide a reason.",

        // Confirmations
        'confirm_sync_env' => 'Are you sure you want to sync variables to .env file?',
        'confirm_clear_cache' => 'Are you sure you want to clear all cache?',
        'confirm_composer_dump' => 'Are you sure you want to run composer dump-autoload?',
        'something_went_wrong' => 'Something went wrong. Please try again.',
        'no_env_variables_yet' => 'No environment variables have been created yet.',

        // Activity status
        'entries' => 'entries',
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
    ],

    // Placeholders
    'placeholders' => [
        'explain_changes' => 'Please explain what changes you made and why...',
        'explain_deletion_reason' => 'Please explain why you are deleting this record...',
        'user_remark_create' => 'Optional: Provide additional context for this entry...',
        'user_remark_update' => 'Please explain why you are making this change...',
        'user_remark_delete' => 'Please explain why you are deleting this record...',
        'user_remark_custom' => 'Please provide your remarks...',
    ],

    // Labels
    'labels' => [
        'name' => 'Name',
        'code' => 'Code',
        'status' => 'Status',
        'created_at' => 'Created At',
        'actions' => 'Actions',
        'reason' => 'Reason',
        'user_remark' => 'Reason for Change',
        'reason_for_change' => 'Reason for Change',
        'activity_logs' => 'Activity Logs',
        'confirm_delete' => 'Confirm Delete',
        'search' => 'Search',
        'view' => 'View',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'country_name' => 'Country',
        'state_name' => 'State',
        'city_name' => 'City',
        'is_ut' => 'Union Territory',
        'deletion_reason' => 'Deletion Reason',
        'parent_category' => 'Parent Category',
        'company_name' => 'Company Name',
        'tag_line' => 'Tag Line',
        'gst_number' => 'GST Number',
        'pancard_number' => 'PAN Card Number',
        'tan_number' => 'TAN Number',

        // Environment Variable Labels
        'env_variable' => [
            'management' => 'Environment Variable Management',
            'list' => 'Environment Variables',
            'list_title' => 'Environment Variables List',
            'create' => 'Create Environment Variable',
            'create_new' => 'Create New Environment Variable',
            'edit' => 'Edit Environment Variable',
            'view' => 'View Environment Variable',
            'delete' => 'Delete Environment Variable',
            'details' => 'Environment Variable Details',
            'key' => 'Variable Key',
            'value' => 'Variable Value',
            'type' => 'Variable Type',
            'category' => 'Category',
            'description' => 'Description',
            'status' => 'Status',
            'is_active' => 'Is Active',
            'is_encrypted' => 'Encrypted',
            'is_sensitive' => 'Sensitive',
            'is_editable' => 'Editable',
            'requires_restart' => 'Requires Restart',
            'encrypted_value' => 'Encrypted Value',
            'created_by' => 'Created By',
            'actions' => 'Actions',
            'system_actions' => 'System Actions',
            'sync_with_env' => 'Sync with .env File',
            'sync_env_file' => 'Sync to .env File',
            'clear_cache' => 'Clear Cache',
            'composer_dump' => 'Composer Dump-Autoload',
            'user_remark' => 'Reason for Change',
            'no_records' => 'No Environment Variables Found',
            'confirm_delete' => 'Confirm Delete',
            'delete_warning' => 'Are you sure you want to delete this environment variable? This action cannot be undone.',
            'sort_order' => 'Sort Order',
            'options' => 'Options',
            'validation_rules' => 'Validation Rules',

            // Type options
            'type_text' => 'Text',
            'type_number' => 'Number',
            'type_boolean' => 'Boolean',
            'type_select' => 'Select',
            'type_password' => 'Password',

            // Field specific
            'enable' => 'Enable',

            // Activity log related
            'updated_by' => 'Updated By',
            'deleted_by' => 'Deleted By',
            'activity_logs' => 'Activity Logs',
            'system_remark' => 'System Remark',
            'view_changes' => 'View Changes',
            'old_values' => 'Old Values',
            'new_values' => 'New Values',
        ],
    ],

    // Products reference (truly global)
    'products' => 'Products',

    // Menu titles (global - for menus without specific modules)
    'general_master' => 'General Masters',
    'masters' => 'Masters',
    'job_cards' => 'Job Cards',
    'manufacturing_master' => 'Manufacturing Masters',
    'raw_material_management' => 'Raw Material Management',
    'product_management' => 'Product Management',

    // Industry menu titles (used by industry menu setup)
    'production' => 'Production',
    'operations' => 'Operations',
    'quality_testing' => 'Quality & Testing',
    'sales_distribution' => 'Sales & Distribution',
    'purchase_supply' => 'Purchase & Supply',
    'inventory' => 'Inventory',
    'vendors_suppliers' => 'Vendors & Suppliers',
    'master_data' => 'Master Data',
    'administration' => 'Administration',
];
