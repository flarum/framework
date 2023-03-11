import app from '../../admin/app';
import DashboardWidget from './DashboardWidget';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import Dropdown from '../../common/components/Dropdown';
import Button from '../../common/components/Button';
import LoadingModal from './LoadingModal';
import LinkButton from '../../common/components/LinkButton';

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

    items.add('version-flarum', [<strong>Flarum</strong>, <br />, app.forum.attribute('version')], 100);
    items.add('version-php', [<strong>PHP</strong>, <br />, app.data.phpVersion], 90);
    items.add('version-mysql', [<strong>MySQL</strong>, <br />, app.data.mysqlVersion], 80);
    if (app.data.schedulerStatus) {
      items.add(
        'schedule-status',
        [
          <span>
            <strong>{app.translator.trans('core.admin.dashboard.status.headers.scheduler-status')}</strong>{' '}
            <LinkButton href="https://discuss.flarum.org/d/24118" external={true} target="_blank" icon="fas fa-info-circle" />
          </span>,
          <br />,
          app.data.schedulerStatus,
        ],
        70
      );
    }

    items.add(
      'queue-driver',
      [<strong>{app.translator.trans('core.admin.dashboard.status.headers.queue-driver')}</strong>, <br />, app.data.queueDriver],
      60
    );
    items.add(
      'session-driver',
      [<strong>{app.translator.trans('core.admin.dashboard.status.headers.session-driver')}</strong>, <br />, app.data.sessionDriver],
      50
    );

    return items;
  }

  toolsItems() {
    const items = new ItemList();

    items.add(
      'clearCache',
      <Button onclick={this.handleClearCache.bind(this)}>{app.translator.trans('core.admin.dashboard.clear_cache_button')}</Button>
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
}
