import app from 'flarum/forum/app';

import FlagListState from './states/FlagListState';
import addFlagControl from './addFlagControl';
import addFlagsDropdown from './addFlagsDropdown';
import addFlagsToPosts from './addFlagsToPosts';

export { default as extend } from './extend';

app.initializers.add('flarum-flags', () => {
  app.flags = new FlagListState(app);

  addFlagControl();
  addFlagsDropdown();
  addFlagsToPosts();
});

// Expose compat API
import flagsCompat from './compat';
import { compat } from '@flarum/core/forum';

Object.assign(compat, flagsCompat);
