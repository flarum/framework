import app from 'flarum/admin/app';
import { extend } from 'flarum/common/extend';

import DashboardPage from 'flarum/admin/components/DashboardPage';

import StatisticsWidget from './components/StatisticsWidget';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

app.initializers.add('flarum-statistics', () => {
  extend(DashboardPage.prototype, 'availableWidgets', function (widgets: ItemList<Mithril.Children>) {
    widgets.add('statistics', <StatisticsWidget />, 20);
  });
});
