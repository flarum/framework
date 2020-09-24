import EventPost from 'flarum/components/EventPost';

export default class DiscussionLockedPost extends EventPost {
  icon() {
    return this.attrs.post.content().locked
      ? 'fas fa-lock'
      : 'fas fa-unlock';
  }

  descriptionKey() {
    return this.attrs.post.content().locked
      ? 'flarum-lock.forum.post_stream.discussion_locked_text'
      : 'flarum-lock.forum.post_stream.discussion_unlocked_text';
  }
}
