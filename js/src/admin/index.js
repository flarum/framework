import 'expose-loader?$!expose-loader?jQuery!jquery';
import 'expose-loader?m!mithril';
import 'expose-loader?moment!moment';
import 'expose-loader?m.bidi!m.attrs.bidi';
import 'bootstrap/js/affix';
import 'bootstrap/js/dropdown';
import 'bootstrap/js/modal';
import 'bootstrap/js/tooltip';
import 'bootstrap/js/transition';

import AdminApplication from './AdminApplication';

const app = new AdminApplication();

// Backwards compatibility
window.app = app;

export { app };

// Export public API


// Export compat API
import compat from './compat';

compat.app = app;

export { compat };
