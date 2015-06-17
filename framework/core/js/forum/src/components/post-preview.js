import Component from 'flarum/component';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';
import humanTime from 'flarum/helpers/human-time';
import highlight from 'flarum/helpers/highlight';
import truncate from 'flarum/utils/truncate';

export default class PostPreview extends Component {
  view() {
    var post = this.props.post;
    var user = post.user();

    var excerpt = post.contentPlain();
    var start = 0;

    if (this.props.highlight) {
      var regexp = new RegExp(this.props.highlight, 'gi');
      start = Math.max(0, excerpt.search(regexp) - 100);
    }

    excerpt = truncate(excerpt, 200, start);

    if (this.props.highlight) {
      excerpt = highlight(excerpt, regexp);
    }

    return m('a.post-preview', {
      href: app.route.post(post),
      config: m.route,
      onclick: this.props.onclick
    }, m('div.post-preview-content', [
      avatar(user), ' ',
      username(user), ' ',
      humanTime(post.time()), ' ',
      excerpt
    ]));
  }
}
