import Post from 'flarum/components/post';
import username from 'flarum/helpers/username';
import icon from 'flarum/helpers/icon';
import humanTime from 'flarum/utils/human-time';

export default class PostActivity extends Post {
  view(iconName, content, attrs) {
    var post = this.props.post;

    attrs = attrs || {};
    attrs.className = 'post-activity post-'+post.contentType()+' '+(attrs.className || '');

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
