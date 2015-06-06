import Component from 'flarum/component';
import avatar from 'flarum/helpers/avatar';

export default class ReplyPlaceholder extends Component {
  view() {
    return m('article.post.reply-post', {onmousedown: () => this.props.discussion.replyAction(true)}, [
      m('header.post-header', avatar(app.session.user()), 'Write a Reply...'),
    ]);
  }
}
