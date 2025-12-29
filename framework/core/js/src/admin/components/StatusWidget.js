import app from '../../admin/app';
import DashboardWidget from './DashboardWidget';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import Dropdown from '../../common/components/Dropdown';
import Button from '../../common/components/Button';
import LoadingModal from './LoadingModal';
import LinkButton from '../../common/components/LinkButton';
import saveSettings from '../utils/saveSettings';
import StatusWidgetItem from './StatusWidgetItem';
import InfoModal from './InfoModal';

export default class StatusWidget extends DashboardWidget {
  className() {
    return 'StatusWidget';
  }

  content() {
    return <ul>{listItems(this.items().toArray())}</ul>;
  }

  items() {
    const items = new ItemList();

    items.add(
      'tools',
      <Dropdown
        label={app.translator.trans('core.admin.dashboard.tools_button')}
        icon="fas fa-cog"
        buttonClassName="Button"
        menuClassName="Dropdown-menu--right"
      >
        {this.toolsItems().toArray()}
      </Dropdown>
    );

    items.add('version-flarum', <StatusWidgetItem label="Flarum" value={app.forum.attribute('version')} icon="fas fa-comments" />, 100);

    items.add('version-php', <StatusWidgetItem label="PHP" value={app.data.phpVersion} icon="fab fa-php" />, 90);

    items.add('version-db', <StatusWidgetItem label={app.data.dbDriver} value={app.data.dbVersion} icon="fas fa-database" />, 80);

    items.add(
      'schedule-status',
      <StatusWidgetItem
        icon="fas fa-clock"
        label={app.translator.trans('core.admin.dashboard.status.headers.scheduler-status')}
        value={
          <span>
            {app.data.schedulerStatus}{' '}
            <LinkButton href="https://docs.flarum.org/scheduler" external={true} target="_blank" icon="fas fa-info-circle" />
          </span>
        }
      />,
      70
    );

    items.add(
      'queue-driver',
      <StatusWidgetItem
        icon="fas fa-list-check"
        label={app.translator.trans('core.admin.dashboard.status.headers.queue-driver')}
        value={app.data.queueDriver}
      />,
      60
    );

    items.add(
      'session-driver',
      <StatusWidgetItem
        icon="fas fa-user-lock"
        label={app.translator.trans('core.admin.dashboard.status.headers.session-driver')}
        value={app.data.sessionDriver}
      />,
      50
    );

    return items;
  }

  toolsItems() {
    const items = new ItemList();

    items.add(
      'clearCache',
      <Button onclick={this.handleClearCache.bind(this)}>{app.translator.trans('core.admin.dashboard.clear_cache_button')}</Button>,
      10
    );

    items.add('info', <Button onclick={this.handleShowInfo.bind(this)}>{app.translator.trans('core.admin.dashboard.info_button')}</Button>, 0);

    items.add(
      'toggleAdvancedPage',
      <Button
        onclick={() => {
          saveSettings({
            show_advanced_settings: !app.data.settings.show_advanced_settings,
          });

          if (app.data.settings.show_advanced_settings) {
            m.route.set(app.route('advanced'));
          }
        }}
      >
        {app.translator.trans('core.admin.dashboard.toggle_advanced_page_button')}
      </Button>
    );

    return items;
  }

  handleClearCache(e) {
    app.modal.show(LoadingModal);

    app
      .request({
        method: 'DELETE',
        url: app.forum.attribute('apiUrl') + '/cache',
      })
      .then(() => window.location.reload())
      .catch((e) => {
        if (e.status === 409) {
          app.alerts.clear();
          app.alerts.show({ type: 'error' }, app.translator.trans('core.admin.dashboard.io_error_message'));
        }

        app.modal.close();
      });
  }

  handleShowInfo() {
    app.modal.show(InfoModal);
  }
}
