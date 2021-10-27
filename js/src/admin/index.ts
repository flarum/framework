import app from './app';

export { app };

// Export public API

// Export compat API
import compatObj from './compat';
import proxifyCompat from '../common/utils/proxifyCompat';

// @ts-ignore
compatObj.app = app;

export const compat = proxifyCompat(compatObj, 'admin');
