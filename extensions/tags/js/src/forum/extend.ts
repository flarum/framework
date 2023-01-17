import app from 'flarum/forum/app';
import Extend from 'flarum/common/extenders';
import IndexPage from 'flarum/forum/components/IndexPage';
import DiscussionTaggedPost from './components/DiscussionTaggedPost';
import TagsPage from './components/TagsPage';

export default [
  new Extend.Routes()
    .add('tags', '/tags', TagsPage)
    .add('tag', '/t/:tags', IndexPage)
    .helper('tag', (tag) => app.route('tag', { tags: tag.slug() })),

  new Extend.PostTypes().add('discussionTagged', DiscussionTaggedPost),
];
