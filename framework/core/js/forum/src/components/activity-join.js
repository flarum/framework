import Component from 'flarum/component';
import humanTime from 'flarum/helpers/human-time';
import avatar from 'flarum/helpers/avatar';

export default class ActivityJoin extends Component {
  view() {
    var activity = this.props.activity;
    var user = activity.user();

    return m('div', [
      avatar(user, {className: 'activity-icon'}),
      m('div.activity-info', [
        m('strong', 'Joined the forum'),
        humanTime(activity.time())
      ])
    ]);
  }
}
