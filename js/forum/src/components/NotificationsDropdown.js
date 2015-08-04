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

    this.list = new NotificationList();
  }

  view() {
    const user = app.session.user;
    const unread = user.unreadNotificationsCount();

    return (
      <div className="Dropdown NotificationsDropdown">
        <a href="javascript:;"
          className={'Dropdown-toggle Button Button--flat NotificationsDropdown-button' + (unread ? ' unread' : '')}
          data-toggle="dropdown"
          onclick={this.onclick.bind(this)}>
          <span className="Button-icon">{unread || icon('bell')}</span>
          <span className="Button-label">{app.trans('core.notifications')}</span>
        </a>
        <div className="Dropdown-menu Dropdown-menu--right" onclick={this.menuClick.bind(this)}>
          {this.showing ? this.list.render() : ''}
        </div>
      </div>
    );
  }

  onclick() {
    if (app.drawer.isOpen()) {
      m.route(app.route('notifications'));
    } else {
      this.showing = true;
      this.list.load();
    }
  }

  menuClick(e) {
    // Don't close the notifications dropdown if the user is opening a link in a
    // new tab or window.
    if (e.shiftKey || e.metaKey || e.ctrlKey || e.which === 2) e.stopPropagation();
  }
}
