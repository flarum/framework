import app from 'flarum/app';
import Model from 'flarum/Model';
import Discussion from 'flarum/models/Discussion';

import DiscussionStickiedPost from './components/DiscussionStickiedPost';
import addStickyBadge from './addStickyBadge';
import addStickyControl from './addStickyControl';
import addStickyExcerpt from './addStickyExcerpt';

app.initializers.add('flarum-sticky', () => {
  app.postComponents.discussionStickied = DiscussionStickiedPost;

  Discussion.prototype.isSticky = Model.attribute('isSticky');
  Discussion.prototype.canSticky = Model.attribute('canSticky');

  addStickyBadge();
  addStickyControl();
  addStickyExcerpt();
});

