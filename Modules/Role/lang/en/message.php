<?php

return [
    // Root level - Basic labels and titles
    'roles' => 'Roles',
    'list' => 'Role List',
    'add' => 'Add Role',
    'edit' => 'Edit Role',
    'name' => 'Role Name',
    'show_role' => 'Show Role',
    'title' => 'Role',
    'details' => 'Role Details',
    'total_users' => 'Total :count users',

    // Year access (roles list cards)
    'year_access' => 'Year Access',
    'restricted' => 'Restricted',
    'full_access' => 'Full Access',
    'all_years_access' => 'Access to all financial years',

    // Permissions
    'role_permissions' => 'Role Permissions',
    'select_all' => 'Select All',
    'administrator_access' => 'Administrator Access',
    'allow_full_access' => 'Allows a full access to the system',
    'permissions' => 'Permissions',

    // Success messages
    'created' => 'Role created successfully',
    'updated' => 'Role updated successfully',
    'deleted' => 'Role deleted successfully',
    'restored' => 'Role restored successfully',
    'activated' => 'Role activated successfully',
    'deactivated' => 'Role deactivated successfully',
    'permissions_updated' => 'Role permissions updated successfully',
    'no_changes' => 'No changes detected. Role was not updated.',

    // Helper text
    'if_not' => 'if it doesn\'t exist.',

    // Error messages
    'error' => [
        'create_failed' => 'Failed to create role',
        'update_failed' => 'Failed to update role',
        'delete_failed' => 'Failed to delete role',
        'not_found' => 'Role not found',
        'access_denied' => 'Access denied',
        'permissions_update_failed' => 'Failed to update role permissions',
    ],

    // Confirmation messages
    'confirm' => [
        'delete' => 'Are you sure you want to delete this role?',
        'permanent_delete' => 'This action cannot be undone',
    ],

    // Validation messages
    'validation' => [
        'enter_name' => 'The role name is required',
        'name' => [
            'required' => 'The role name is required',
            'unique' => 'The role name already exists',
            'min' => 'The role name must be at least :min characters',
            'max' => 'The role name cannot exceed :max characters',
        ],
        'permissions' => [
            'required' => 'At least one permission must be selected',
        ],

    ],

    // Labels for forms and displays
    'labels' => [
        'deletion_reason' => 'Deletion Reason',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'created_by' => 'Created By',
        'status' => 'Status',
        'actions' => 'Actions',
    ],

    // Buttons and actions
    'buttons' => [
        'save' => 'Save',
        'update' => 'Update',
        'delete' => 'Delete',
        'cancel' => 'Cancel',
        'back' => 'Back',
        'submit' => 'Submit',
        'reset' => 'Reset',
        'search' => 'Search',
        'clear' => 'Clear',
        'export' => 'Export',
        'import' => 'Import',
        'close' => 'Close',
        'add_new' => 'Add New Role',
        'view_details' => 'View Details',
        'edit_record' => 'Edit Role',
        'delete_record' => 'Delete Role',
        'restore' => 'Restore',
        'assign_permissions' => 'Assign Permissions',
    ],

    // Placeholders and helper text
    'placeholders' => [
        'search' => 'Search roles...',
        'select_option' => 'Select an option',
        'enter_name' => 'Enter role name',
        'permissions_search' => 'Search permissions...',
    ],

    // Tooltips and instructions
    'tooltips' => [
        'required_field' => 'This field is required',
        'optional_field' => 'This field is optional',
        'select_all_permissions' => 'Select all permissions for this role',
        'administrator_access' => 'Grants full system access to this role',
    ],

    'instructions' => [
        'permission_selection' => 'Select the permissions that should be granted to this role',
    ],

    // Legacy support - keeping for backward compatibility
    'common' => [
        'back' => 'Back',
        'cancel' => 'Cancel',
        'submit' => 'Submit',
        'all' => 'All',
    ],
];
