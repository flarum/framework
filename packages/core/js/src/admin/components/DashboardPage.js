import Page from '../../common/components/Page';
import StatusWidget from './StatusWidget';
import ExtensionsWidget from './ExtensionsWidget';
import AdminHeader from './AdminHeader';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';

export default class DashboardPage extends Page {
  view() {
    return (
      <div className="DashboardPage">
        <AdminHeader icon="fas fa-chart-bar" description={app.translator.trans('core.admin.dashboard.description')} className="DashboardPage-header">
          {app.translator.trans('core.admin.dashboard.title')}
        </AdminHeader>
        <div className="container">{this.availableWidgets().toArray()}</div>
      </div>
    );
  }

  availableWidgets() {
    const items = new ItemList();

    items.add('status', <StatusWidget />, 30);

    items.add('extensions', <ExtensionsWidget />, 10);

    return items;
  }
}
