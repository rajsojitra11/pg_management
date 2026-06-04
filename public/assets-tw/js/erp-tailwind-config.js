/* ERP Tailwind Config — reads from CSS variables defined in erp-overrides.css :root
   To change the theme: edit the --erp-* variables in erp-overrides.css, everything updates. */
tailwind.config = {
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
      },
      colors: {
        /* Map Tailwind tokens to CSS variables */
        border: 'var(--erp-border)',
        input: 'var(--erp-border)',
        ring: 'var(--erp-primary)',
        background: 'var(--erp-bg)',
        foreground: 'var(--erp-text)',
        primary: {
          DEFAULT: 'var(--erp-primary)',
          foreground: 'var(--erp-primary-fg)',
        },
        secondary: {
          DEFAULT: 'var(--erp-bg-muted)',
          foreground: 'var(--erp-text)',
        },
        destructive: {
          DEFAULT: 'var(--erp-danger-bg-solid)',
          foreground: 'var(--erp-primary-fg)',
        },
        muted: {
          DEFAULT: 'var(--erp-bg-muted)',
          foreground: 'var(--erp-text-secondary)',
        },
        accent: {
          DEFAULT: 'var(--erp-bg-muted)',
          foreground: 'var(--erp-text)',
        },
        card: {
          DEFAULT: 'var(--erp-bg)',
          foreground: 'var(--erp-text)',
        },
      },
      borderRadius: {
        lg: '0.5rem',
        md: '0.375rem',
        sm: '0.25rem',
      },
      fontSize: {
        '2xs': ['0.625rem', { lineHeight: '0.875rem' }],
      },
      boxShadow: {
        'sm': '0 1px 2px 0 rgb(0 0 0 / 0.05)',
      },
      keyframes: {
        'fade-in': {
          '0%': { opacity: '0', transform: 'translateY(-4px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        'fade-out': {
          '0%': { opacity: '1', transform: 'translateY(0)' },
          '100%': { opacity: '0', transform: 'translateY(-4px)' },
        },
        'slide-in-right': {
          '0%': { transform: 'translateX(100%)' },
          '100%': { transform: 'translateX(0)' },
        },
        'slide-out-right': {
          '0%': { transform: 'translateX(0)' },
          '100%': { transform: 'translateX(100%)' },
        },
      },
      animation: {
        'fade-in': 'fade-in 0.15s ease-out',
        'fade-out': 'fade-out 0.15s ease-in',
        'slide-in-right': 'slide-in-right 0.2s ease-out',
        'slide-out-right': 'slide-out-right 0.2s ease-in',
      },
    },
  },
};
