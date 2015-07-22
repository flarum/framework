import Model from 'flarum/Model';
import Discussion from 'flarum/models/Discussion';
import IndexPage from 'flarum/components/IndexPage';

import Tag from 'tags/models/Tag';
import TagsPage from 'tags/components/TagsPage';
import DiscussionTaggedPost from 'tags/components/DiscussionTaggedPost';

import addTagList from 'tags/addTagList';
import addTagFilter from 'tags/addTagFilter';
import addTagLabels from 'tags/addTagLabels';
import addTagControl from 'tags/addTagControl';
import addTagComposer from 'tags/addTagComposer';

app.initializers.add('tags', function(app) {
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
