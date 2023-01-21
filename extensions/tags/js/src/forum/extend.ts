import app from 'flarum/forum/app';
import Extend from 'flarum/common/extenders';
import IndexPage from 'flarum/forum/components/IndexPage';
import Discussion from 'flarum/common/models/Discussion';
import DiscussionTaggedPost from './components/DiscussionTaggedPost';
import TagsPage from './components/TagsPage';
import Tag from '../common/models/Tag';

import commonExtend from '../common/extend';

export default [
  ...commonExtend,

  new Extend.Routes() //
    .add('tags', '/tags', TagsPage) //
    .add('tag', '/t/:tags', IndexPage) //
    .helper('tag', (tag) => app.route('tag', { tags: tag.slug() })),

  new Extend.PostTypes() //
    .add('discussionTagged', DiscussionTaggedPost),

  new Extend.Model(Discussion) //
    .hasMany<Tag>('tags') //
    .attribute<boolean>('canTag'),
];
