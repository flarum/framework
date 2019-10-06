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
import compat from './compat';

compat.app = app;

export { compat };
