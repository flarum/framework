import app from 'flarum/app';
import { extend } from 'flarum/extend';

import DashboardPage from 'flarum/components/DashboardPage';

import StatisticsWidget from './components/StatisticsWidget';

app.initializers.add('flarum-statistics', () => {
  extend(DashboardPage.prototype, 'availableWidgets', widgets => {
    widgets.add('statistics', <StatisticsWidget/>, 20);
  });
});
