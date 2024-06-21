import mixin from '@flarum/core/src/common/utils/mixin';
import ExportRegistry from '@flarum/core/src/common/ExportRegistry';
import jquery from 'jquery';
import m from 'mithril';
import dayjs from 'dayjs';
import './test-matchers';

import relativeTime from 'dayjs/plugin/relativeTime';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import jsdom from 'jsdom';

dayjs.extend(relativeTime);
dayjs.extend(localizedFormat);

process.env.testing = true;

const dom = new jsdom.JSDOM('', {
  pretendToBeVisual: false,
});

// Fill in the globals Mithril.js needs to operate. Also, the first two are often
// useful to have just in tests.
global.window = dom.window;
global.document = dom.window.document;
global.requestAnimationFrame = (callback) => callback();

// Some other needed pollyfills.
window.$ = jquery;
window.m = m;
window.$.fn.tooltip = () => {};
window.matchMedia = () => ({
  addListener: () => {},
  removeListener: () => {},
});
window.scrollTo = () => {};

// Flarum specific globals.
global.flarum = {
  extensions: {},
  reg: new (mixin(ExportRegistry, {
    checkModule: () => true,
  }))(),
};

// Prepare basic dom structure.
document.body.innerHTML = `
<div id="app">
  <main class="App-content">
    <div id="notices"></div>
    <div id="content"></div>
  </main>
</div>
`;

beforeEach(() => {
  flarum.reg.clear();
});

afterAll(() => {
  dom.window.close();
});
