import app from 'flarum/forum/app';
import Model from 'flarum/common/Model';
import Discussion from 'flarum/common/models/Discussion';
import IndexPage from 'flarum/forum/components/IndexPage';

import TagListState from '../common/states/TagListState';
import Tag from '../common/models/Tag';
import TagsPage from './components/TagsPage';
import DiscussionTaggedPost from './components/DiscussionTaggedPost';

import addTagList from './addTagList';
import addTagFilter from './addTagFilter';
import addTagLabels from './addTagLabels';
import addTagControl from './addTagControl';
import addTagComposer from './addTagComposer';

app.initializers.add('flarum-tags', function () {
  app.routes.tags = { path: '/tags', component: TagsPage };
  app.routes.tag = { path: '/t/:tags', component: IndexPage };

  app.route.tag = (tag: Tag) => app.route('tag', { tags: tag.slug() });

  app.postComponents.discussionTagged = DiscussionTaggedPost;

  app.store.models.tags = Tag;

  app.tagList = new TagListState();

  Discussion.prototype.tags = Model.hasMany<Tag>('tags');
  Discussion.prototype.canTag = Model.attribute<boolean>('canTag');

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
