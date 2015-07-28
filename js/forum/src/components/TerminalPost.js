import Component from 'flarum/Component';
import humanTime from 'flarum/helpers/humanTime';

/**
 * Displays information about a the first or last post in a discussion.
 *
 * ### Props
 *
 * - `discussion`
 * - `lastPost`
 */
export default class TerminalPost extends Component {
  view() {
    const discussion = this.props.discussion;
    const lastPost = this.props.lastPost && discussion.repliesCount();

    const user = discussion[lastPost ? 'lastUser' : 'startUser']();
    const time = discussion[lastPost ? 'lastTime' : 'startTime']();

    return (
      <span>
        {app.trans('core.discussion_' + (lastPost ? 'replied' : 'started'), {
          user,
          ago: humanTime(time)
        })}
      </span>
    );
  }
}
