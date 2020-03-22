import 'expose-loader?ColorThief!colorthief/dist/color-thief.umd';

import app from './app';

export { app };

// Export compat API
import compat from './compat';

compat.app = app;

export { compat };
