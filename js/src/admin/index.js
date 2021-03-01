import AdminApplication from './AdminApplication';

const app = new AdminApplication();

// Backwards compatibility
window.app = app;

export { app };

// Export public API

// Export compat API
import compatObj from './compat';
import proxifyCompat from '../common/utils/proxifyCompat';

compatObj.app = app;

export const compat = proxifyCompat(compatObj, 'admin');
