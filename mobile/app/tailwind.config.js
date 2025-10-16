/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./App.{js,jsx,ts,tsx}",
    "./app/**/*.{js,jsx,ts,tsx}",
    "./src/**/*.{js,jsx,ts,tsx}",
    "./components/**/*.{js,jsx,ts,tsx}",
    "./screens/**/*.{js,jsx,ts,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Primary deep blue (card header, active tab)
        primary: {
          DEFAULT: '#1543A6', // main
          50: '#F1F4FF',
          100: '#E6EBFF',
          200: '#CCD8FF',
          300: '#9FB6FF',
          400: '#6E90F5',
          500: '#3767E8',
          600: '#1543A6',
          700: '#0F3582',
          800: '#0C2A68',
          900: '#0A2356',
        },
        // Backgrounds and surfaces
        bg: {
          DEFAULT: '#F4F6FE', // page background
          soft: '#EEF1FC',    // cards container bg
          card: '#FFFFFF',
        },
        // Text tokens
        text: {
          DEFAULT: '#0B0F1A',
          muted: '#6B7280',
          inverse: '#FFFFFF',
        },
        // Status
        success: {
          DEFAULT: '#16A34A',
          bg: '#EAFBEF',
        },
        warning: {
          DEFAULT: '#F59E0B',
          bg: '#FFF6DB',
        },
        info: {
          DEFAULT: '#3B82F6',
          bg: '#E8F1FF',
        },
        // Borders and separators
        border: {
          DEFAULT: '#E8ECF8', // subtle card border
          strong: '#D7DDF0',
        },
      },
      borderRadius: {
        sm: '6px',
        md: '10px',
        lg: '14px',
      },
    },
  },
  plugins: [],
};
