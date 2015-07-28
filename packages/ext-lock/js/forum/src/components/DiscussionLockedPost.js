import EventPost from 'flarum/components/EventPost';

export default class DiscussionLockedPost extends EventPost {
  icon() {
    return this.props.post.content().locked
      ? 'lock'
      : 'unlock';
  }

  descriptionKey() {
    return this.props.post.content().locked
      ? 'lock.discussion_locked_post'
      : 'lock.discussion_unlocked_post';
  }
}
