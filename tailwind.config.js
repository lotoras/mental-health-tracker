import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'mental-excellent': {
                    DEFAULT: '#10b981', // emerald-500
                    light: '#34d399',   // emerald-400
                    dark: '#059669',    // emerald-600
                },
                'mental-good': {
                    DEFAULT: '#3b82f6', // blue-500
                    light: '#60a5fa',   // blue-400
                    dark: '#2563eb',    // blue-600
                },
                'mental-okay': {
                    DEFAULT: '#fbbf24', // amber-400
                    light: '#fcd34d',   // amber-300
                    dark: '#f59e0b',    // amber-500
                },
                'mental-bad': {
                    DEFAULT: '#fb923c', // orange-400
                    light: '#fdba74',   // orange-300
                    dark: '#f97316',    // orange-500
                },
                'mental-worst': {
                    DEFAULT: '#ef4444', // red-500
                    light: '#f87171',   // red-400
                    dark: '#dc2626',    // red-600
                },
            },
        },
    },

    plugins: [forms],
};
