import EventPost from 'flarum/components/EventPost';

export default class DiscussionStickiedPost extends EventPost {
  icon() {
    return 'thumb-tack';
  }

  descriptionKey() {
    return this.props.post.content().sticky
      ? 'sticky.discussion_stickied_post'
      : 'sticky.discussion_unstickied_post';
  }
}
