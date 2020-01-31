import 'flarum-webpack-config/shims';

import Forum from './src/forum/Forum';

declare global {
    const app: Forum;
}
