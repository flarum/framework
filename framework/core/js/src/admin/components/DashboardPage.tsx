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

    if (app.data.pausedQueues?.length) {
      items.add(
        'queuePaused',
        <AlertWidget
          alert={{
            type: 'warning',
            dismissible: false,
            controls: [
              <Link className="Button Button--link" href={app.route('advanced')}>
                {app.translator.trans('core.admin.dashboard.queue_paused_manage')}
              </Link>,
            ],
          }}
        >
          {app.data.pausedQueues.includes('*')
            ? app.translator.trans('core.admin.dashboard.queue_paused_all_warning')
            : app.translator.trans('core.admin.dashboard.queue_paused_warning', { queues: app.data.pausedQueues.join(', ') })}
        </AlertWidget>,
        105
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

    // A running install can never be below the hard minimum — Flarum won't
    // boot below it, and the installer refuses to proceed — so we only nudge
    // when the version is below the recommended release.
    const dbVersionStatus = app.data.dbVersionStatus;
    if (dbVersionStatus && dbVersionStatus.status === 'below_recommended') {
      items.add(
        'db-version-warning',
        <AlertWidget
          className="DbVersionWarningWidget"
          alert={{
            type: 'warning',
            dismissible: false,
            title: app.translator.trans('core.admin.database-version-warning.below-recommended-label'),
            icon: 'fas fa-database',
          }}
        >
          {app.translator.trans('core.admin.database-version-warning.below-recommended-detail', {
            server: dbVersionStatus.server,
            version: dbVersionStatus.version,
            recommended: dbVersionStatus.recommended,
            link: ({ children }: { children: Children }) => (
              <Link href="https://docs.flarum.org/2.x/install/#server-requirements" external={true} target="_blank">
                <Icon name="fas fa-external-link-alt" />
                {children}
              </Link>
            ),
          })}
        </AlertWidget>,
        85
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
