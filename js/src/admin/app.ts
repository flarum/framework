import Admin from './AdminApplication';

const app = new Admin();

// @ts-expect-error We need to do this for backwards compatibility purposes.
window.app = app;

export default app;
