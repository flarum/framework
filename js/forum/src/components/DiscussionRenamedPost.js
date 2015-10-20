import EventPost from 'flarum/components/EventPost';

/**
 * The `DiscussionRenamedPost` component displays a discussion event post
 * indicating that the discussion has been renamed.
 *
 * ### Props
 *
 * - All of the props for EventPost
 */
export default class DiscussionRenamedPost extends EventPost {
  icon() {
    return 'pencil';
  }

  descriptionKey() {
    return 'core.forum.post_stream.discussion_renamed_text';
  }

  descriptionData() {
    const post = this.props.post;
    const oldTitle = post.content()[0];
    const newTitle = post.content()[1];

    return {
      'old': <strong className="DiscussionRenamedPost-old">{oldTitle}</strong>,
      'new': <strong className="DiscussionRenamedPost-new">{newTitle}</strong>
    };
  }
}
