// Expose punycode and ColorThief to the window browser object
import 'expose-loader?exposes=punycode!punycode';
import 'expose-loader?exposes=ColorThief!color-thief-browser';

import app from './app';

export { app };

import './forum';
