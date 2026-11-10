import 'iframe-resizer/js/iframeResizer.contentWindow.js';
import app from 'flarum/forum/app';

import overrideLinks from './overrideLinks';
import extendPostStream from './extendPostStream';
import extendDiscussionPage from './extendDiscussionPage';
import setupIframeResizer from './iframeResizer';

export { default as extend } from './extend';

app.initializers.add('flarum-embed', () => {
  overrideLinks();
  extendPostStream();
  extendDiscussionPage();
  setupIframeResizer();
});
