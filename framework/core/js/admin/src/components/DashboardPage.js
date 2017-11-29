import Page from 'flarum/components/Page';
import StatusWidget from 'flarum/components/StatusWidget';
import StatisticsWidget from 'flarum/components/StatisticsWidget';

export default class DashboardPage extends Page {
  view() {
    return (
      <div className="DashboardPage">
        <div className="container">
          <StatusWidget/>
          <StatisticsWidget/>
        </div>
      </div>
    );
  }
}
