import Component from 'flarum/Component';
import icon from 'flarum/helpers/icon';
import NotificationList from 'flarum/components/NotificationList';

export default class NotificationsDropdown extends Component {
  constructor(...args) {
    super(...args);

    /**
     * Whether or not the notifications dropdown is visible.
     *
     * @type {Boolean}
     */
    this.showing = false;
  }

  view() {
    const user = app.session.user;
    const unread = user.unreadNotificationsCount();

    return (
      <div className="dropdown btn-group notifications-dropdown">
        <a href="javascript:;"
          className={'dropdown-toggle btn btn-default btn-rounded btn-naked btn-icon' + (unread ? ' unread' : '')}
          data-toggle="dropdown"
          onclick={this.onclick.bind(this)}>
          <span className="notifications-icon">{unread || icon('bell')}</span>
          <span className="label">Notifications</span>
        </a>
        <div className="dropdown-menu dropdown-menu-right">
          {this.showing ? NotificationList.component() : ''}
        </div>
      </div>
    );
  }

  onclick() {
    if (app.drawer.isOpen()) {
      m.route(app.route('notifications'));
    } else {
      this.showing = true;
    }
  }
}
