import Post from 'flarum/components/post';
import username from 'flarum/helpers/username';
import icon from 'flarum/helpers/icon';
import humanTime from 'flarum/utils/human-time';
import { dasherize } from 'flarum/utils/string';

export default class EventPost extends Post {
  view(iconName, content, attrs) {
    var post = this.props.post;

    attrs = attrs || {};
    attrs.className = 'event-post post-'+dasherize(post.contentType())+' '+(attrs.className || '');

    return super.view([
      icon(iconName+' post-icon'),
      m('div.event-post-info', [
        m('a.post-user', {href: app.route('user', { username: post.user().username() }), config: m.route}, username(post.user())), ' ',
        content
      ]),
      m('div.event-post-time', humanTime(post.time()))
    ], attrs);
  }
}
