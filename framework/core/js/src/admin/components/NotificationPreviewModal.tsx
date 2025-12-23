import app from '../../admin/app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Button from '../../common/components/Button';
import Select from '../../common/components/Select';

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
      html: string;
      text: string;
    } | null;
    extension: string | null;
  };
}

interface NotificationPreview {
  type: string;
  driver: string;
  content: string | object;
  subject?: string;
}

export interface INotificationPreviewModalAttrs extends IInternalModalAttrs {
  notificationType: NotificationType;
}

export default class NotificationPreviewModal extends Modal<INotificationPreviewModalAttrs> {
  selectedDriver!: string;
  previewContent: NotificationPreview | null = null;
  loading: boolean = false;

  oninit(vnode: any) {
    super.oninit(vnode);

    const type = this.attrs.notificationType;
    this.selectedDriver = type.attributes.capabilities.email ? 'email-html' : 'alert';
    this.loadPreview();
  }

  className() {
    return 'NotificationPreviewModal Modal--large';
  }

  title() {
    return app.translator.trans('core.admin.notification_templates.preview_modal_title', {
      type: this.attrs.notificationType.attributes.type,
    });
  }

  content() {
    const type = this.attrs.notificationType;

    return (
      <div className="Modal-body">
        <div className="NotificationPreviewModal-controls">
          <label>{app.translator.trans('core.admin.notification_templates.driver_label')}</label>
          <Select
            value={this.selectedDriver}
            options={this.getDriverOptions()}
            onchange={(value: string) => {
              this.selectedDriver = value;
              this.loadPreview();
            }}
          />
        </div>

        <div className="NotificationPreviewModal-preview">
          {this.loading ? (
            <LoadingIndicator />
          ) : this.previewContent ? (
            <div>
              {this.previewContent.subject && (
                <div className="EmailSubject">
                  <strong>{app.translator.trans('core.admin.notification_templates.email_subject')}:</strong> {this.previewContent.subject}
                </div>
              )}

              {this.renderPreviewContent()}
            </div>
          ) : (
            <p className="helpText">{app.translator.trans('core.admin.notification_templates.select_driver')}</p>
          )}
        </div>

        <div className="NotificationPreviewModal-help">
          <p className="helpText">{app.translator.trans('core.admin.notification_templates.preview_help')}</p>
        </div>
      </div>
    );
  }

  getDriverOptions() {
    const type = this.attrs.notificationType;
    const options: Record<string, string> = {};

    if (type.attributes.capabilities.alert) {
      options.alert = app.translator.trans('core.admin.notification_templates.drivers.alert');
    }

    if (type.attributes.capabilities.email) {
      options['email-html'] = app.translator.trans('core.admin.notification_templates.drivers.email_html');
      options['email-plain'] = app.translator.trans('core.admin.notification_templates.drivers.email_plain');
    }

    return options;
  }

  async loadPreview() {
    this.loading = true;
    this.previewContent = null;
    m.redraw();

    try {
      const response = await app.request<NotificationPreview>({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/notification-preview',
        params: {
          blueprint: this.attrs.notificationType.attributes.blueprintClass,
          driver: this.selectedDriver,
        },
      });

      this.previewContent = response;
    } catch (error) {
      console.error('Failed to load preview:', error);
      this.previewContent = null;
    } finally {
      this.loading = false;
      m.redraw();
    }
  }

  renderPreviewContent() {
    if (!this.previewContent) return null;

    if (this.selectedDriver === 'email-html') {
      return <iframe className="EmailPreview" srcdoc={this.previewContent.content as string} sandbox="allow-same-origin" />;
    } else if (this.selectedDriver === 'email-plain') {
      return <pre className="PlainTextPreview">{this.previewContent.content}</pre>;
    } else {
      // Alert driver - show JSON
      return (
        <div className="AlertPreview">
          <pre>{JSON.stringify(this.previewContent.content, null, 2)}</pre>
        </div>
      );
    }
  }
}
