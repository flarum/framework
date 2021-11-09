import app from './app';

export { app };

// Export public API

// Export compat API
import compatObj from './compat';
import proxifyCompat from '../common/utils/proxifyCompat';

// @ts-expect-error The `app` instance needs to be available on compat.
compatObj.app = app;

export const compat = proxifyCompat(compatObj, 'admin');
