import Page from './Page';
import StatusWidget from './StatusWidget';

export default class DashboardPage extends Page {
  view() {
    return (
      <div className="DashboardPage">
        <div className="container">
          {this.availableWidgets()}
        </div>
      </div>
    );
  }

  availableWidgets() {
    return [<StatusWidget/>];
  }
}
