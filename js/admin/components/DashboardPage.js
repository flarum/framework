import Page from 'components/Page';
import StatusWidget from 'components/StatusWidget';

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
