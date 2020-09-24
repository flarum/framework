import Component from '../../common/Component';
import humanTime from '../../common/helpers/humanTime';
import icon from '../../common/helpers/icon';

/**
 * Displays information about a the first or last post in a discussion.
 *
 * ### Attrs
 *
 * - `discussion`
 * - `lastPost`
 */
export default class TerminalPost extends Component {
  view() {
    const discussion = this.attrs.discussion;
    const lastPost = this.attrs.lastPost && discussion.replyCount();

    const user = discussion[lastPost ? 'lastPostedUser' : 'user']();
    const time = discussion[lastPost ? 'lastPostedAt' : 'createdAt']();

    return (
      <span>
        {lastPost ? icon('fas fa-reply') : ''}{' '}
        {app.translator.trans('core.forum.discussion_list.' + (lastPost ? 'replied' : 'started') + '_text', {
          user,
          ago: humanTime(time),
        })}
      </span>
    );
  }
}
