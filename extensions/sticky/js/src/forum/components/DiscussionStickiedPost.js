import EventPost from 'flarum/components/EventPost';

export default class DiscussionStickiedPost extends EventPost {
  icon() {
    return 'fas fa-thumbtack';
  }

  descriptionKey() {
    return this.attrs.post.content().sticky
      ? 'flarum-sticky.forum.post_stream.discussion_stickied_text'
      : 'flarum-sticky.forum.post_stream.discussion_unstickied_text';
  }
}
