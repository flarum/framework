export * from './webpack-flarum-shims';

import Forum from './src/forum/Forum';

declare global {
    const app: Forum;
}
