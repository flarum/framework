import Page from 'flarum/components/Page';
import StatusWidget from 'flarum/components/StatusWidget';

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
