# Theme UI System

## Layout Base File

- Authenticated app layout: `resources/views/layouts-tw/app-tw.blade.php`
- Sidebar: `resources/views/layouts-tw/sidebar.blade.php`
- Sidebar nav: `resources/views/layouts-tw/sidebar-nav-items.blade.php`
- Header: `resources/views/layouts-tw/header.blade.php`
- Footer: `resources/views/layouts-tw/footer.blade.php`
- Guest layout: `resources/views/layouts/guest-tw.blade.php`

## Blade Structure Rules

- Reuse layout partials from `resources/views/layouts-tw`.
- Reuse modal partials from `resources/views/partials-tw`.
- Keep module views inside `Modules/{Module}/resources/views`.
- Prefer route names over hardcoded URLs.
- Keep module language strings in `Modules/{Module}/lang/en/message.php`.

## CSS Framework

- Tailwind CSS 4 through Vite.
- ERP theme helpers under `public/assets-tw/js` and `resources/views/css`.

## Primary Colors

Theme colors are controlled by settings/config and ERP CSS generation through
`/css/erp-config.css`. Use existing CSS variables/classes rather than hardcoded
module colors.

## Component Standards

- DataTables for listing pages.
- Select2 for enhanced selects/typeaheads.
- Flatpickr for date inputs.
- SweetAlert-style modals for destructive/status actions where existing helpers
  are used.
- Shared delete/logout/status modals from `partials-tw`.

## Button And Card Classes

Use existing Blade and Tailwind classes from sibling module views. Do not create
new button/card systems inside modules.
