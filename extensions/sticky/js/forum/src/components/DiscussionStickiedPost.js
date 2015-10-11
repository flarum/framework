import EventPost from 'flarum/components/EventPost';

export default class DiscussionStickiedPost extends EventPost {
  icon() {
    return 'thumb-tack';
  }

  descriptionKey() {
    return this.props.post.content().sticky
      ? 'flarum-sticky.forum.discussion_stickied_post'
      : 'flarum-sticky.forum.discussion_unstickied_post';
  }
}
