/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}"
  ],
  theme: {
    extend: {
      backgroundImage: {
        'gradient-purple-blue': 'linear-gradient(90deg, #6F4EF2 0%, #53BDFF 100%)'
      },
      fontFamily: {
        nasalization: ['Nasalization-Regular', 'sans-serif'],
        rogueland: ['NCS_Rogueland', 'sans-serif'],
      },
    },
  },
  plugins: [],
}