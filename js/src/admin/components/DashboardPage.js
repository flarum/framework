import AdminPage from './AdminPage';
import StatusWidget from './StatusWidget';

export default class DashboardPage extends AdminPage {
  view() {
    return (
      <div className="DashboardPage">
        <div className="container">{this.availableWidgets()}</div>
      </div>
    );
  }

  availableWidgets() {
    return [<StatusWidget />];
  }
}
