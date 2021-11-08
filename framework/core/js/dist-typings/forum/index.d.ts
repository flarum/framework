import 'expose-loader?exposes=punycode!punycode';
import 'expose-loader?exposes=ColorThief!color-thief-browser';
import app from './app';
export { app };
export declare const compat: Record<string, unknown>;
