import Component from 'flarum/Component';
import avatar from 'flarum/helpers/avatar';
import DiscussionControls from 'flarum/utils/DiscussionControls';

/**
 * The `ReplyPlaceholder` component displays a placeholder for a reply, which,
 * when clicked, opens the reply composer.
 *
 * ### Props
 *
 * - `discussion`
 */
export default class ReplyPlaceholder extends Component {
  view() {
    function triggerClick(e) {
      $(this).trigger('click');
      e.preventDefault();
    }

    const reply = () => {
      DiscussionControls.replyAction.call(this.props.discussion, true);
    };

    return (
      <article className="Post ReplyPlaceholder" onclick={reply} onmousedown={triggerClick}>
        <header className="Post-header">
          {avatar(app.session.user, {className: 'PostUser-avatar'})}{' '}
          {app.trans('core.write_a_reply')}
        </header>
      </article>
    );
  }
}
