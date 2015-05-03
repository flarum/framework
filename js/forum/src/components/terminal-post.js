import Component from 'flarum/component';
import humanTime from 'flarum/utils/human-time';
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

    return m('span', [
      username(discussion[lastPost ? 'lastUser' : 'startUser']()),
      lastPost ? ' replied ' : ' started ',
      m('time', humanTime(discussion[lastPost ? 'lastTime' : 'startTime']()))
    ])
  }
}
