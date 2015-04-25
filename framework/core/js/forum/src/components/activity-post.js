import Component from 'flarum/component';
import humanTime from 'flarum/helpers/human-time';
import avatar from 'flarum/helpers/avatar';

export default class ActivityPost extends Component {
  view() {
    var activity = this.props.activity;
    var user = activity.user();
    var post = activity.post();
    var discussion = post.discussion();

    return m('div', [
      avatar(user, {className: 'activity-icon'}),
      m('div.activity-info', [
        m('strong', post.number() == 1 ? 'Started a discussion' : 'Posted a reply'),
        humanTime(activity.time())
      ]),
      m('a.activity-content.activity-post', {href: app.route('discussion.near', {
        id: discussion.id(),
        slug: discussion.slug(),
        near: post.number()
      }), config: m.route}, [
        m('h3.title', discussion.title()),
        m('div.body', m.trust(post.contentHtml()))
      ])
    ]);
  }
}
