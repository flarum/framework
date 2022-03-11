// Expose jQuery, mithril and dayjs to the window browser object
import 'expose-loader?exposes=$,jQuery!jquery';
import 'expose-loader?exposes=m!mithril';
import 'expose-loader?exposes=dayjs!dayjs';

import 'bootstrap/js/affix';
import 'bootstrap/js/dropdown';
import 'bootstrap/js/modal';
import 'bootstrap/js/tooltip';
import 'bootstrap/js/transition';
import 'jquery.hotkeys/jquery.hotkeys';

import relativeTime from 'dayjs/plugin/relativeTime';
import localizedFormat from 'dayjs/plugin/localizedFormat';

dayjs.extend(relativeTime);
dayjs.extend(localizedFormat);

import patchMithril from './utils/patchMithril';

patchMithril(window);

import * as Extend from './extend/index';
import app from './app';

export { Extend, app };

import './utils/arrayFlatPolyfill';

const tooltipGen = $.fn.tooltip;

// Remove in a future version of Flarum.
// @ts-ignore
$.fn.tooltip = function (options, caller) {
  // Show a warning when `$.tooltip` is used outside of the Tooltip component.
  // This functionality is deprecated and should not be used.
  if (!['DANGEROUS_tooltip_jquery_fn_deprecation_exempt'].includes(caller)) {
    console.warn(
      "Calling `$.tooltip` is now deprecated. Please use the `<Tooltip>` component exposed by flarum/core instead. `$.tooltip` may be removed in a future version of Flarum.\n\nIf this component doesn't meet your requirements, please open an issue: https://github.com/flarum/core/issues/new?assignees=davwheat&labels=type/bug,needs-verification&template=bug-report.md&title=Tooltip%20component%20unsuitable%20for%20use%20case"
    );
  }

  tooltipGen.bind(this)(options);
};
