import app from '../../admin/app';
import StatusWidget from './StatusWidget';
import ExtensionsWidget from './ExtensionsWidget';
import ItemList from '../../common/utils/ItemList';
import AdminPage from './AdminPage';
import type { Children } from 'mithril';
import AlertWidget from './AlertWidget';
import Link from '../../common/components/Link';

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

    if (app.data.maintenanceMode) {
      items.add(
        'maintenanceMode',
        <AlertWidget
          alert={{
            type: 'error',
            dismissible: false,
          }}
        >
          {app.translator.trans('core.lib.notices.maintenance_mode_' + app.data.maintenanceMode)}
        </AlertWidget>,
        110
      );
    }

    if (app.data.debugEnabled) {
      items.add(
        'debug-warning',
        <AlertWidget
          className="DebugWarningWidget"
          alert={{
            type: 'warning',
            dismissible: false,
            title: app.translator.trans('core.admin.debug-warning.label'),
            icon: 'fas fa-exclamation-triangle',
          }}
        >
          {app.translator.trans('core.admin.debug-warning.detail', {
            link: <Link href="https://docs.flarum.org/troubleshoot/#step-0-activate-debug-mode" external={true} target="_blank" />,
          })}
        </AlertWidget>,
        100
      );
    }

    items.add('status', <StatusWidget />, 30);

    items.add('extensions', <ExtensionsWidget />, 10);

    return items;
  }
}
