import app from 'flarum/forum/app';

import FlagListState from './states/FlagListState';
import addFlagControl from './addFlagControl';
import addFlagsDropdown from './addFlagsDropdown';
import addFlagsToPosts from './addFlagsToPosts';
import extendRealtime from './extendRealtime';

export { default as extend } from './extend';

app.initializers.add('flarum-flags', () => {
  app.flags = new FlagListState(app);

  addFlagControl();
  addFlagsDropdown();
  addFlagsToPosts();

  // Register a flag badge update event with flarum/realtime when enabled.
  // When a post is flagged or flags are cleared, moderators' flag badge
  // count updates in real-time without a page reload (fixes #4437).
  if ('flarum-realtime' in flarum.extensions) {
    extendRealtime();
  }
});

import './forum';
