import app from 'flarum/forum/app';

import addStickyBadge from './addStickyBadge';
import addStickyControl from './addStickyControl';
import addStickyExcerpt from './addStickyExcerpt';
import addStickyClass from './addStickyClass';
import extendRealtime from './extendRealtime';

export { default as extend } from './extend';

app.initializers.add('flarum-sticky', () => {
  addStickyBadge();
  addStickyControl();
  addStickyExcerpt();
  addStickyClass();

  // Register a discussion stream update event with flarum/realtime when enabled.
  // Stickying or unstickying a discussion will trigger a DiscussionPage stream reload for other users.
  if ('flarum-realtime' in flarum.extensions) {
    extendRealtime();
  }
});
