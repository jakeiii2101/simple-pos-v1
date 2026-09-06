import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                'sniper-navy': '#0F2747',
                'sniper-red': '#E50914',
                'sniper-slate': '#64748B',
                'sniper-light': '#F1F5F9',
                'sniper-dark': '#08111F',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                heading: ['Montserrat', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                'sniper': '12px',
            },
            boxShadow: {
                'sniper': '0 4px 16px rgba(15, 39, 71, 0.08)',
            },
        },
    },

    plugins: [forms],
};
