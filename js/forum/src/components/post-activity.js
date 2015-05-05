import Post from 'flarum/components/post';
import username from 'flarum/helpers/username';
import icon from 'flarum/helpers/icon';
import humanTime from 'flarum/utils/human-time';

export default class PostActivity extends Post {
  view(content, attrs) {
    attrs.className = 'post-activity '+(attrs.className || '');

    var iconName = attrs.icon;
    delete attrs.icon;

    var post = this.props.post;

    return super.view([
      icon(iconName+' post-icon'),
      m('div.post-activity-info', [
        m('a.post-user', {href: app.route('user', { username: post.user().username() }), config: m.route}, username(post.user())), ' ',
        content
      ]),
      m('div.post-activity-time', humanTime(post.time()))
    ], attrs);
  }
}
