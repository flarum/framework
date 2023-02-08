import app from 'flarum/forum/app';
import Model from 'flarum/common/Model';
import Discussion from 'flarum/common/models/Discussion';

import addStickyBadge from './addStickyBadge';
import addStickyControl from './addStickyControl';
import addStickyExcerpt from './addStickyExcerpt';
import addStickyClass from './addStickyClass';

export { default as extend } from './extend';

app.initializers.add('flarum-sticky', () => {
  Discussion.prototype.isSticky = Model.attribute('isSticky');
  Discussion.prototype.canSticky = Model.attribute('canSticky');

  addStickyBadge();
  addStickyControl();
  addStickyExcerpt();
  addStickyClass();
});
