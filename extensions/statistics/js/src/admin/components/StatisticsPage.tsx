import ExtensionPage from 'flarum/admin/components/ExtensionPage';

import StatisticsWidget from './StatisticsWidget';

export default class StatisticsPage extends ExtensionPage {
  content() {
    return (
      <div className="StatisticsPage">
        <div className="container">
          <StatisticsWidget />
        </div>
      </div>
    );
  }
}
