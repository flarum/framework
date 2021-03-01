import 'expose-loader?punycode!punycode';
import 'expose-loader?ColorThief!color-thief-browser';

import ForumApplication from './ForumApplication';

const app = new ForumApplication();

// Backwards compatibility
window.app = app;

export { app };

// Export public API
// export { default as Extend } from './Extend';
// export { IndexPage, DicsussionList } from './components';

// Export compat API
import compatObj from './compat';
import proxifyCompat from '../common/utils/proxifyCompat';

compatObj.app = app;

export const compat = proxifyCompat(compatObj, 'forum');
