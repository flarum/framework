import app from '../../admin/app';
import StatusWidget from './StatusWidget';
import ExtensionsWidget from './ExtensionsWidget';
import AnnouncementsWidget from './AnnouncementsWidget';
import ItemList from '../../common/utils/ItemList';
import AdminPage from './AdminPage';
import type { Children } from 'mithril';
import AlertWidget from './AlertWidget';
import Link from '../../common/components/Link';
import Icon from '../../common/components/Icon';

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

    if (app.data.bisecting) {
      items.add(
        'bisecting',
        <AlertWidget
          alert={{
            type: 'error',
            dismissible: false,
            controls: [
              <Link className="Button Button--link" href={app.route('advanced', { modal: 'extension-bisect' })}>
                {app.translator.trans('core.lib.notices.bisecting_continue')}
              </Link>,
            ],
          }}
        >
          {app.translator.trans('core.lib.notices.bisecting')}
        </AlertWidget>,
        120
      );
    }

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

    if (app.data.dbDriverMismatch) {
      items.add(
        'db-driver-mismatch-warning',
        <AlertWidget
          className="DbDriverMismatchWarningWidget"
          alert={{
            type: 'error',
            dismissible: false,
            title: app.translator.trans('core.admin.database-driver-mismatch-warning.label'),
            icon: 'fas fa-database',
          }}
        >
          {app.translator.trans('core.admin.database-driver-mismatch-warning.detail', {
            configured: app.data.dbDriver as string,
            actual: app.data.dbDriverMismatch as string,
            link: ({ children }: { children: Children }) => (
              <Link href="https://docs.flarum.org/install/#database" external={true} target="_blank">
                <Icon name="fas fa-external-link-alt" />
                {children}
              </Link>
            ),
          })}
        </AlertWidget>,
        90
      );
    }

    items.add('status', <StatusWidget />, 80);

    if (!app.data.announcementsDisabled) {
      items.add('announcements', <AnnouncementsWidget />, 70);
    }

    items.add('extensions', <ExtensionsWidget />, 10);

    return items;
  }
}
