import Forum from './Forum';

const app = new Forum();

// @ts-ignore
window.app = app;

export { app };

// Export compat API
import compat from './compat';

compat.app = app;

export { compat };
