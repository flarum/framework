import Component from 'flarum/Component';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';
import DiscussionControls from 'flarum/utils/DiscussionControls';
import formatText from 'flarum/utils/formatText';

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
    if (app.composingReplyTo(this.props.discussion)) {
      return (
        <article className="Post CommentPost editing">
          <header className="Post-header">
            <div className="PostUser">
              <h3>
                {avatar(app.session.user, {className: 'PostUser-avatar'})}
                {username(app.session.user)}
              </h3>
            </div>
          </header>
          <div className="Post-body">
            {m.trust(formatText(this.props.discussion.replyContent))}
          </div>
        </article>
      );
    }

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
