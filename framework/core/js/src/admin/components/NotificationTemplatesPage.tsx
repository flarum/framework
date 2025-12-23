import app from '../../admin/app';
import AdminPage from './AdminPage';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Button from '../../common/components/Button';
import Icon from '../../common/components/Icon';
import NotificationPreviewModal from './NotificationPreviewModal';
import type { IPageAttrs } from '../../common/components/Page';
import type Mithril from 'mithril';

interface NotificationType {
  id: string;
  type: string;
  attributes: {
    type: string;
    blueprintClass: string;
    subjectModel: string | null;
    defaultDrivers: string[];
    capabilities: {
      alert: boolean;
      email: boolean;
    };
    emailViews: {
      text: string;
      html: string;
    } | null;
    extension: string | null;
  };
}

export default class NotificationTemplatesPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends AdminPage<CustomAttrs> {
  notificationTypes: NotificationType[] = [];

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.loadNotificationTypes();
  }

  headerInfo() {
    return {
      className: 'NotificationTemplatesPage',
      icon: 'fas fa-bell',
      title: app.translator.trans('core.admin.notification_templates.title'),
      description: app.translator.trans('core.admin.notification_templates.description'),
    };
  }

  async loadNotificationTypes() {
    this.loading = true;

    try {
      const response = await app.request<{ data: NotificationType[] }>({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/notification-types',
      });

      this.notificationTypes = response.data;
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  content() {
    if (this.loading) {
      return <LoadingIndicator />;
    }

    return (
      <div className="NotificationTemplatesPage-content">
        <div className="NotificationTemplatesPage-list">
          <table className="NotificationTable">
            <thead>
              <tr>
                <th>{app.translator.trans('core.admin.notification_templates.table.type')}</th>
                <th>{app.translator.trans('core.admin.notification_templates.table.extension')}</th>
                <th>{app.translator.trans('core.admin.notification_templates.table.drivers')}</th>
                <th>{app.translator.trans('core.admin.notification_templates.table.actions')}</th>
              </tr>
            </thead>
            <tbody>
              {this.notificationTypes.map((type) => (
                <tr key={type.id}>
                  <td>
                    <strong>{type.attributes.type}</strong>
                    <br />
                    <small className="NotificationTable-class">{type.attributes.blueprintClass}</small>
                  </td>
                  <td>{this.renderExtension(type.attributes.extension)}</td>
                  <td>{this.renderDriverBadges(type)}</td>
                  <td>
                    <Button
                      className="Button Button--primary"
                      icon="fas fa-eye"
                      onclick={() => {
                        app.modal.show(NotificationPreviewModal, { notificationType: type });
                      }}
                    >
                      {app.translator.trans('core.admin.notification_templates.preview_button')}
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    );
  }

  renderExtension(extensionId: string | null) {
    if (!extensionId) {
      return <span className="Badge Badge--core">Core</span>;
    }

    // Look up the extension in app.data.extensions
    const extension = app.data.extensions[extensionId];
    const displayName = extension?.extra['flarum-extension']?.title || extensionId;

    return (
      <span className="Badge Badge--extension" title={extensionId}>
        {displayName}
      </span>
    );
  }

  renderDriverBadges(type: NotificationType) {
    const badges = [];

    if (type.attributes.capabilities.alert) {
      badges.push(
        <span className="Badge Badge--alert" title={app.translator.trans('core.admin.notification_templates.drivers.alert_description')}>
          <Icon name="fas fa-bell" /> {app.translator.trans('core.admin.notification_templates.drivers.alert')}
        </span>
      );
    }

    if (type.attributes.capabilities.email) {
      badges.push(
        <span className="Badge Badge--email" title={app.translator.trans('core.admin.notification_templates.drivers.email_description')}>
          <Icon name="fas fa-envelope" /> {app.translator.trans('core.admin.notification_templates.drivers.email')}
        </span>
      );
    }

    return <div className="NotificationDrivers">{badges}</div>;
  }
}
