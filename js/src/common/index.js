import 'expose-loader?jQuery!zepto';
import 'expose-loader?moment!expose-loader?dayjs!dayjs';
import 'expose-loader?m!mithril';
import 'expose-loader?m.bidi!m.attrs.bidi';
import 'expose-loader?Mousetrap!mousetrap';

import 'zepto/src/selector';
import 'zepto/src/data';
import 'zepto/src/fx';
import 'zepto/src/fx_methods';

import './utils/patchZepto';

import 'hc-sticky';
import 'bootstrap/js/dropdown';
import 'bootstrap/js/modal';
import 'bootstrap/js/tooltip';
import 'bootstrap/js/transition';

import relativeTime from 'dayjs/plugin/relativeTime';
import localizedFormat from 'dayjs/plugin/localizedFormat';

dayjs.extend(relativeTime);
dayjs.extend(localizedFormat);

import patchMithril from './utils/patchMithril';

patchMithril(window);

import * as Extend from './extend/index';

export { Extend };
