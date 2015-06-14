import app from 'flarum/app';
import Model from 'flarum/model';
import Discussion from 'flarum/models/discussion';
import IndexPage from 'flarum/components/index-page';

import Tag from 'flarum-tags/models/tag';
import TagsPage from 'flarum-tags/components/tags-page';
import DiscussionTaggedPost from 'flarum-tags/components/discussion-tagged-post';
import addTagList from 'flarum-tags/add-tag-list';
import addTagFilter from 'flarum-tags/add-tag-filter';
import addTagLabels from 'flarum-tags/add-tag-labels';
import addTagDiscussionControl from 'flarum-tags/add-tag-discussion-control';
import addTagComposer from 'flarum-tags/add-tag-composer';

app.initializers.add('flarum-tags', function() {
  // Register routes.
  app.routes['tags'] = ['/tags', TagsPage.component()];
  app.routes['tag'] = ['/t/:tags', IndexPage.component()];

  // Register models.
  app.store.models['tags'] = Tag;
  Discussion.prototype.tags = Model.many('tags');
  Discussion.prototype.canTag = Model.prop('canTag');

  app.postComponentRegistry['discussionTagged'] = DiscussionTaggedPost;

  // Add a list of tags to the index navigation.
  addTagList();

  // When a tag is selected, filter the discussion list by that tag.
  addTagFilter();

  // Add tags to the discussion list and discussion hero.
  addTagLabels();

  addTagDiscussionControl();

  addTagComposer();
});
