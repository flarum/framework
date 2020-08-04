export * from './webpack-config-shims';

import Application from './src/common/Application';

declare global {
    const app: Application;
}