/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["*.{php, html}", "./Components/*.php"],
  theme: {
    extend: {
      animation: {
        popsUp: 'popsUp 0.4s;'
      },
      keyframes: {
        popsUp: {
          '0%': { transform: 'scale(1)' },
          '60%': { transform: 'scale(1.1)' },
          '100%': { transform: 'scale(1)' }
        }
      }
    },
  },
  plugins: [],
}

