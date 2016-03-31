import Button from 'flarum/components/Button';
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
  init() {
    super.init();

    this.expanded = false;

    // Rerender the post content when we toggle the details.
    this.subtree.check(
      () => this.expanded
    );
  }

  icon() {
    return 'pencil';
  }

  description(data) {
    return [
      app.translator.trans('core.forum.post_stream.discussion_renamed_text', data),
      this.toggleButton(),
      this.expanded ? this.full(data) : null
    ];
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

  full(data) {
    return [
      <br />,
      app.translator.trans('core.forum.post_stream.discussion_renamed_old_text', data),
      <br />,
      app.translator.trans('core.forum.post_stream.discussion_renamed_new_text', data)
    ];
  }

  collapsed() {
    return this.toggleButton();
  }

  toggle() {
    this.expanded = !this.expanded;
  }

  toggleButton() {
    return Button.component({
      className: 'Button Button--default Button--more',
      icon: 'ellipsis-h',
      onclick: this.toggle.bind(this)
    });
  }
}
