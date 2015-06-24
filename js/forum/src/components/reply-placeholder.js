import Component from 'flarum/component';
import avatar from 'flarum/helpers/avatar';

export default class ReplyPlaceholder extends Component {
  view() {
    return m('article.post.reply-post', {
      onclick: () => this.props.discussion.replyAction(true),
      onmousedown: (e) => {
        $(e.target).trigger('click');
        e.preventDefault();
      }
    }, [
      m('header.post-header', avatar(app.session.user()), ' Write a Reply...'),
    ]);
  }
}
