import app from 'flarum/admin/app';
import { extend } from 'flarum/common/extend';

import DashboardPage from 'flarum/admin/components/DashboardPage';

import MiniStatisticsWidget from './components/MiniStatisticsWidget';
import StatisticsPage from './components/StatisticsPage';

app.initializers.add('flarum-statistics', () => {
  extend(DashboardPage.prototype, 'availableWidgets', function (widgets) {
    widgets.add('statistics', <MiniStatisticsWidget />, 20);
  });

  app.extensionData.for('flarum-statistics').registerPage(StatisticsPage);
});
