// Expose punycode and ColorThief to the window browser object
import 'expose-loader?exposes=punycode!punycode';
import 'expose-loader?exposes=ColorThief!color-thief-browser';

import app from './app';

export { app };

// Export compat API
import compatObj from './compat';
import proxifyCompat from '../common/utils/proxifyCompat';

// @ts-ignore
compatObj.app = app;

export const compat = proxifyCompat(compatObj, 'forum');
