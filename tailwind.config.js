/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./**/*.{html,php}"],
  darkMode: ["selector", '[data-theme="dark"]'],
  theme: {
    fontFamily: {
      "brand-display": ["'Tilt Neon'", "Arial", "Helvetica", "sans-serif"],
      "brand-body": ["'PT Sans'", "sans-serif", "system-ui"]
    },
    colors: {
      //inspiration:
      //https://dribbble.com/shots/17266165-Dev-Agency-Website-Design-Dark-theme

      //tool and resources
      //https://colorffy.com/dark-theme-generator
      //https://tailwindcss.com/docs/customizing-colors

      "bg-lighter": "#f3f4f6",
      "bg-light": "#d1d5db",
      "bg-light-less": "#bcc0c5",

      "surface-light-100": "#9ca3af",
      "surface-light-200": "#a7adb8",
      "surface-light-300": "#b1b7c0",

      "bg-darker": "#0B0B0B",
      "bg-dark": "#252525",

      "brand-primary-100": "#ffa27c",
      "brand-primary-200": "#ff8260",
      "brand-primary-300": "#ff6145",
      "brand-primary-400": "#fa3f2b",
      "brand-primary-500-neutral": "#D61010",
      "brand-primary-600": "#ab0d0d",
      "brand-primary-700": "#800a0a",
      "brand-primary-800": "#6b0808",
      "brand-secondary": "#161F45",
      "brand-secondary-100": "#413866",
      "brand-secondary-200": "#6e5387",
      "brand-secondary-300": "#9d6fa6",
      "brand-secondary-400": "#d08cc4",

      "surface-dark-100": "#232323",
      "surface-dark-200": "#3a3a3a",
      "surface-dark-300": "#535353",

      "bg-info": "#009a80"
    },
    extend: {}
  },
  content: ["./**/*.php", "./**/*.html", "./**/*.css", "./**/*.js"],
  safelist: [
    "card-3d",
    "animate-ping",
    "bg-lighter",
    "bg-light",
    "surface-light-100",
    "surface-light-200",
    "surface-light-300",
    "bg-darker",
    "bg-dark",
    "brand-primary-100",
    "brand-primary-200",
    "brand-primary-300",
    "brand-primary-500-neutral",
    "brand-primary-600",
    "brand-primary-700",
    "brand-primary-800",
    "brand-secondary",
    "surface-dark-100",
    "surface-dark-200",
    "surface-dark-300",
    "bg-info"
  ],
  plugins: [require("tailwindcss-animated")]
};
