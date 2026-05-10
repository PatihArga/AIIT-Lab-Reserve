import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    safelist: [
        'lg:max-w-[256px]',
        'lg:max-w-0',
        'shadow-2xl',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans:    ['Sora', ...defaultTheme.fontFamily.sans],
                display: ['Sora', ...defaultTheme.fontFamily.sans],
                mono:    ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },

            colors: {
                /* === SURFACES === */
                bg:      '#FAFAF7',     // warm off-white page background
                surface: '#FFFFFF',     // inset surfaces (used sparingly)

                /* === INK (deep navy — primary brand) === */
                ink: {
                    DEFAULT: '#0F2460',
                    900:     '#0A1A47',  // sidebar background
                    800:     '#0F2460',  // brand surface
                    700:     '#1A3C8F',
                    600:     '#2952B3',
                    500:     '#3B68D4',
                    400:     '#5585E8',
                    100:     '#DBEAFE',
                    50:      '#EEF2FF',
                },

                /* === MARK (yellow — the ONE highlight) === */
                mark: {
                    DEFAULT: '#F5B800',
                    600:     '#D9A300',  // hover
                    500:     '#F5B800',
                    400:     '#F7C933',
                    100:     '#FEF3C7',
                    50:      '#FFFBEB',
                },

                /* === Rule color (faint navy hairlines) === */
                rule: 'rgba(15, 36, 96, 0.08)',
                'rule-strong': 'rgba(15, 36, 96, 0.16)',

                /* === STATUS (semantic — outline-first) === */
                status: {
                    approved:    '#16A34A',
                    rejected:    '#DC2626',
                    pending:     '#D97706',
                    review:      '#7C3AED',
                    cancelled:   '#6B7280',
                    completed:   '#0891B2',
                    draft:       '#9CA3AF',
                },
            },

            borderColor: {
                DEFAULT: 'rgba(15, 36, 96, 0.08)',
            },

            borderRadius: {
                DEFAULT: '6px',
                'sm':    '4px',
                'md':    '6px',
                'lg':    '8px',
                'xl':    '12px',
            },

            boxShadow: {
                'subtle': '0 1px 2px rgba(15, 36, 96, 0.04)',
                'card':   '0 1px 3px rgba(15, 36, 96, 0.06), 0 1px 2px rgba(15, 36, 96, 0.04)',
                'modal':  '0 24px 48px rgba(15, 36, 96, 0.18)',
                'focus':  '0 0 0 3px rgba(85, 133, 232, 0.25)',
            },

            spacing: {
                'shell': '256px',  // sidebar width
            },

            maxWidth: {
                'content':  '1280px',
                'reading':  '720px',  // booking flow / forms
            },

            letterSpacing: {
                'label': '0.16em',  // for uppercase section labels
            },

            fontSize: {
                'hero':  ['3.5rem', { lineHeight: '1', letterSpacing: '-0.02em', fontWeight: '700' }],
                'stat':  ['2.75rem', { lineHeight: '1', letterSpacing: '-0.01em', fontWeight: '600' }],
            },
        },
    },

    plugins: [forms],
};
