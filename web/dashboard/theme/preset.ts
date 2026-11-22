// PrimeVue theme built on Aura, recoloured with the prototype's brand palette (teal primary, navy surfaces).
import Aura from '@primeuix/themes/aura'
import { definePreset } from '@primeuix/themes'

export const DentLegalPreset = definePreset(Aura, {
  semantic: {
    primary: {
      50: '#ebf7f4',
      100: '#d3efe9',
      200: '#a9e0d5',
      300: '#7fd1c2',
      400: '#3fbaa4',
      500: '#16a08a',
      600: '#12806f',
      700: '#0d6b5d',
      800: '#0b564b',
      900: '#08443b',
      950: '#052b25',
    },
    colorScheme: {
      light: {
        primary: { color: '{primary.600}', contrastColor: '#ffffff', hoverColor: '{primary.700}', activeColor: '{primary.800}' },
        highlight: { background: '{primary.50}', focusBackground: '{primary.100}', color: '{primary.700}', focusColor: '{primary.800}' },
      },
    },
  },
})
