export * from './webpack-flarum-shims';

import Application from './src/common/Application';

declare global {
    const app: Application;
}
