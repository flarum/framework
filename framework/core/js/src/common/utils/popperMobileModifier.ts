import { type Modifier } from "@popperjs/core";

export default {
  name: 'responsiveMobile',
  enabled: true,
  phase: 'beforeWrite',
  fn({ state }) {
    const screen = getComputedStyle(state.elements.popper).getPropertyValue('--flarum-screen');
    if (screen == 'phone') {
      state.styles.popper = {
        margin: null as unknown as string,
        position: null as unknown as string,
        left: null as unknown as string,
        top: null as unknown as string,
        bottom: null as unknown as string,
        transform: null as unknown as string
      };
    }
  }
} satisfies Modifier<any, any>;