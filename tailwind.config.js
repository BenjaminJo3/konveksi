/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                ink: '#182321',
                moss: '#1c8064',
                emerald: {
                    50: '#f8f8f8',
                    100: '#eeeeef',
                    200: '#d9dadd',
                    300: '#b7b9bd',
                    400: '#85888e',
                    500: '#62656b',
                    600: '#46494f',
                    700: '#292b30',
                    800: '#1f2024',
                    900: '#151619',
                },
            },
        },
    },
    plugins: [],
};
