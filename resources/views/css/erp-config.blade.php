:root {
    --erp-logo-bg-login-hero: {{ config('business.logo.bg_login_hero', 'rgba(255,255,255,0.15)') }};
    --erp-logo-bg-login-card: {{ config('business.logo.bg_login_card', 'transparent') }};
    --erp-logo-bg-horizontal: {{ config('business.logo.bg_horizontal', 'transparent') }};
    --erp-logo-bg-sidebar: {{ config('business.logo.bg_sidebar', 'transparent') }};
    --erp-logo-bg-sidebar-sm: {{ config('business.logo.bg_sidebar_sm', 'transparent') }};
    --erp-logo-bg-mobile: {{ config('business.logo.bg_mobile', 'transparent') }};
}
html.dark {
    --erp-logo-bg-login-card: {{ config('business.logo.bg_login_card_dark', 'transparent') }};
    --erp-logo-bg-horizontal: {{ config('business.logo.bg_horizontal_dark', '#ffffff') }};
    --erp-logo-bg-sidebar: {{ config('business.logo.bg_sidebar_dark', '#ffffff') }};
    --erp-logo-bg-sidebar-sm: {{ config('business.logo.bg_sidebar_sm_dark', '#ffffff') }};
    --erp-logo-bg-mobile: {{ config('business.logo.bg_mobile_dark', '#ffffff') }};
}
