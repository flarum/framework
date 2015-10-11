import NotificationsDropdown from 'flarum/components/NotificationsDropdown';

import FlagList from 'flarum/flags/components/FlagList';

export default class FlagsDropdown extends NotificationsDropdown {
  static initProps(props) {
    props.label = props.label || 'Flagged Posts';
    props.icon = props.icon || 'flag';

    super.initProps(props);
  }

  constructor(...args) {
    super(...args);

    this.list = new FlagList();
  }

  goToRoute() {
    m.route(app.route('flags'));
  }

  getUnreadCount() {
    return app.forum.attribute('unreadFlagsCount');
  }

  getNewCount() {
    return app.forum.attribute('newFlagsCount');
  }
}
