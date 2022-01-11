import app from 'flarum/forum/app';
import Model from 'flarum/common/Model';
import Discussion from 'flarum/common/models/Discussion';

import DiscussionStickiedPost from './components/DiscussionStickiedPost';
import addStickyBadge from './addStickyBadge';
import addStickyControl from './addStickyControl';
import addStickyExcerpt from './addStickyExcerpt';
import addStickyClass from './addStickyClass';

app.initializers.add('flarum-sticky', () => {
  app.postComponents.discussionStickied = DiscussionStickiedPost;

  Discussion.prototype.isSticky = Model.attribute('isSticky');
  Discussion.prototype.canSticky = Model.attribute('canSticky');

  addStickyBadge();
  addStickyControl();
  addStickyExcerpt();
  addStickyClass();
});
