import app from 'flarum/forum/app';

import addStickyBadge from './addStickyBadge';
import addStickyControl from './addStickyControl';
import addStickyExcerpt from './addStickyExcerpt';
import addStickyClass from './addStickyClass';

export { default as extend } from './extend';

app.initializers.add('flarum-sticky', () => {
  addStickyBadge();
  addStickyControl();
  addStickyExcerpt();
  addStickyClass();
});

// Expose compat API
import stickyCompat from './compat';
import { compat } from '@flarum/core/forum';

Object.assign(compat, stickyCompat);
