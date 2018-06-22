import 'expose-loader?$!expose-loader?jQuery!jquery';
import 'expose-loader?m!mithril';
import 'expose-loader?moment!moment';
import 'expose-loader?m.bidi!m.attrs.bidi';
import 'bootstrap/js/affix';
import 'bootstrap/js/dropdown';
import 'bootstrap/js/modal';
import 'bootstrap/js/tooltip';
import 'bootstrap/js/transition';
import 'jquery.hotkeys/jquery.hotkeys';

import patchMithril from './utils/patchMithril';

patchMithril(window);

import * as Extend from './extend/index';

export { Extend };
