import Component from 'flarum/component';
import humanTime from 'flarum/helpers/human-time';
import username from 'flarum/helpers/username';

/**
  Displays information about a the first or last post in a discussion.

  @prop discussion {Discussion} The discussion to display the post for
  @prop lastPost {Boolean} Whether or not to display the last/start post
  @class TerminalPost
  @constructor
  @extends Component
 */
export default class TerminalPost extends Component {
  view() {
    var discussion = this.props.discussion;
    var lastPost = this.props.lastPost && discussion.repliesCount();

    var user = discussion[lastPost ? 'lastUser' : 'startUser']();
    var time = discussion[lastPost ? 'lastTime' : 'startTime']();

    return m('span', [
      username(user),
      lastPost ? ' replied ' : ' started ',
      humanTime(time)
    ])
  }
}
