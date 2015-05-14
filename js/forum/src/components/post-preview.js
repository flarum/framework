import Component from 'flarum/component';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';
import humanTime from 'flarum/helpers/human-time';

export default class PostPreview extends Component {
  view() {
    var post = this.props.post;
    var user = post.user();

    return m('a.post-preview', {
      href: app.route.post(post),
      config: m.route,
      onclick: this.props.onclick
    }, m('div.post-preview-content', [
      avatar(user), ' ',
      username(user), ' ',
      humanTime(post.time()), ' ',
      post.excerpt()
    ]));
  }
}
