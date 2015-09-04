import NotificationsDropdown from 'flarum/components/NotificationsDropdown';

import ReportList from 'reports/components/ReportList';

export default class ReportsDropdown extends NotificationsDropdown {
  static initProps(props) {
    props.label = props.label || 'Reports';
    props.icon = props.icon || 'flag';

    super.initProps(props);
  }

  constructor(...args) {
    super(...args);

    this.list = new ReportList();
  }

  goToRoute() {
    m.route(app.route('reports'));
  }

  getUnreadCount() {
    return app.forum.attribute('unreadReportsCount');
  }
}
