import 'expose-loader?$!expose-loader?jQuery!jquery';
import 'expose-loader?m!mithril';
import 'expose-loader?moment!moment';
import 'expose-loader?Spinner!spin.js';
import 'expose-loader?punycode!punycode';
import 'expose-loader?ColorThief!color-thief-browser';
import 'expose-loader?m.bidi!m.attrs.bidi';
import 'bootstrap/js/dropdown';
import 'bootstrap/js/modal';
import 'bootstrap/js/tooltip';
import 'bootstrap/js/transition';

import ForumApplication from './ForumApplication';

const app = new ForumApplication();

// Backwards compatibility
window.app = app;

export { app };

export const extensions = {};

// Export public API
// export { default as Extend } from './Extend';
// export { IndexPage, DicsussionList } from './components';

// Export deprecated API
import deprecated from './deprecated';

deprecated.app = app;

export { deprecated };
