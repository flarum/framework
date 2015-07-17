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

  description() {
    const post = this.props.post;
    const oldTitle = post.content()[0];
    const newTitle = post.content()[1];

    return ['changed the title from ', m('strong.DiscussionRenamedPost-old', oldTitle), ' to ', m('strong.DiscussionRenamedPost-new', newTitle), '.'];
  }
}
