import Model from 'flarum/Model';
import Discussion from 'flarum/models/Discussion';
import IndexPage from 'flarum/components/IndexPage';

import Tag from '../common/models/Tag';
import TagsPage from './components/TagsPage';
import DiscussionTaggedPost from './components/DiscussionTaggedPost';

import addTagList from './addTagList';
import addTagFilter from './addTagFilter';
import addTagLabels from './addTagLabels';
import addTagControl from './addTagControl';
import addTagComposer from './addTagComposer';

app.initializers.add('flarum-tags', function(app) {
  app.routes.tags = {path: '/tags', component: TagsPage.component()};
  app.routes.tag = {path: '/t/:tags', component: IndexPage.component()};

  app.route.tag = tag => app.route('tag', {tags: tag.slug()});

  app.postComponents.discussionTagged = DiscussionTaggedPost;

  app.store.models.tags = Tag;

  Discussion.prototype.tags = Model.hasMany('tags');
  Discussion.prototype.canTag = Model.attribute('canTag');

  addTagList();
  addTagFilter();
  addTagLabels();
  addTagControl();
  addTagComposer();
});


// Expose compat API
import tagsCompat from './compat';
import { compat } from '@flarum/core/forum';

Object.assign(compat, tagsCompat);