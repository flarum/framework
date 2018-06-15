import 'expose-loader?$!expose-loader?jQuery!jquery';
import 'expose-loader?m!mithril';
import 'expose-loader?moment!moment';
import 'expose-loader?punycode!punycode';
import 'expose-loader?ColorThief!color-thief-browser';
import 'expose-loader?m.bidi!m.attrs.bidi';
import 'bootstrap/js/affix';
import 'bootstrap/js/dropdown';
import 'bootstrap/js/modal';
import 'bootstrap/js/tooltip';
import 'bootstrap/js/transition';
import 'jquery.hotkeys/jquery.hotkeys';

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
