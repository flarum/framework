// Expose jQuery, mithril and dayjs to the window browser object
import 'expose-loader?exposes=$,jQuery!jquery';
import 'expose-loader?exposes=m!mithril';
import 'expose-loader?exposes=dayjs!dayjs';

import Dropdown from 'bootstrap/js/dist/dropdown';
import relativeTime from 'dayjs/plugin/relativeTime';
import localizedFormat from 'dayjs/plugin/localizedFormat';

import popperMobileModifier from './utils/popperMobileModifier';

Dropdown.Default.popperConfig = {
  strategy: 'fixed',
  modifiers: [popperMobileModifier],
};

dayjs.extend(relativeTime);
dayjs.extend(localizedFormat);

import './registry';

import patchMithril from './utils/patchMithril';

patchMithril(window);

import app from './app';

export { app };

import './utils/arrayFlatPolyfill';
