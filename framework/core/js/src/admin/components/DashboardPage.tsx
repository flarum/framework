import app from '../../admin/app';
import StatusWidget from './StatusWidget';
import ExtensionsWidget from './ExtensionsWidget';
import ItemList from '../../common/utils/ItemList';
import AdminPage from './AdminPage';
import type { Children } from 'mithril';

export default class DashboardPage extends AdminPage {
  headerInfo() {
    return {
      className: 'DashboardPage',
      icon: 'fas fa-chart-bar',
      title: app.translator.trans('core.admin.dashboard.title'),
      description: app.translator.trans('core.admin.dashboard.description'),
    };
  }

  content() {
    return this.availableWidgets().toArray();
  }

  availableWidgets(): ItemList<Children> {
    const items = new ItemList<Children>();

    items.add('status', <StatusWidget />, 30);

    items.add('extensions', <ExtensionsWidget />, 10);

    return items;
  }
}
