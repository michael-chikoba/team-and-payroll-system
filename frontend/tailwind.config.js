/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: 'hsl(var(--primary))',
        'primary-dark': 'hsl(var(--primary-dark))',
        secondary: 'hsl(var(--secondary))',
        ticket: 'hsl(var(--ticket))',
      },
      fontFamily: {
        sans: ['Inter', 'Segoe UI', 'Tahoma', 'Geneva', 'Verdana', 'sans-serif'],
      },
      zIndex: {
        '90': '90',
        '99': '99',
      },
    },
  },
  plugins: [],
}