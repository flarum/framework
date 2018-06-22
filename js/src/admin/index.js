import AdminApplication from './AdminApplication';

const app = new AdminApplication();

// Backwards compatibility
window.app = app;

export { app };

// Export public API


// Export compat API
import compat from './compat';

compat.app = app;

export { compat };
