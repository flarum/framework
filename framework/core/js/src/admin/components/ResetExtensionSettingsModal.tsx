import app from '../../admin/app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Button from '../../common/components/Button';
import Tooltip from '../../common/components/Tooltip';
import Alert from '../../common/components/Alert';
import LoadingModal from './LoadingModal';
import extractText from '../../common/utils/extractText';
import type Mithril from 'mithril';

export interface ResetSettingItem {
  key: string;
  label?: Mithril.Children;
}

export interface IResetExtensionSettingsModalAttrs extends IInternalModalAttrs {
  settings: ResetSettingItem[];
  extensionId?: string;
  title?: string;
}

export default class ResetExtensionSettingsModal<
  CustomAttrs extends IResetExtensionSettingsModalAttrs = IResetExtensionSettingsModalAttrs
> extends Modal<CustomAttrs> {
  protected loading = false;

  className() {
    return 'ResetExtensionSettingsModal Modal--medium';
  }

  title() {
    return this.attrs.title ?? app.translator.trans('core.admin.extension.reset_settings.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <Alert type="warning" dismissible={false}>
          {app.translator.trans('core.admin.extension.reset_settings.warning')}
        </Alert>
        <ul className="ResetExtensionSettingsModal-keys">
          {this.attrs.settings.map(({ key, label }) => (
            <li>
              <Tooltip text={key} position="right">
                <span>{label ? extractText(label) : key}</span>
              </Tooltip>
            </li>
          ))}
        </ul>
        <div className="Form-group Form-controls">
          <Button className="Button Button--danger" loading={this.loading} onclick={this.confirm.bind(this)}>
            {app.translator.trans('core.admin.extension.reset_settings.confirm_button')}
          </Button>
          <Button className="Button" onclick={() => this.hide()}>
            {app.translator.trans('core.admin.extension.reset_settings.cancel_button')}
          </Button>
        </div>
      </div>
    );
  }

  async confirm() {
    this.loading = true;
    m.redraw();

    try {
      await app.request({
        method: 'DELETE',
        url: app.forum.attribute('apiUrl') + '/settings',
        body: { keys: this.attrs.settings.map(({ key }) => key), extensionId: this.attrs.extensionId ?? '' },
      });

      this.hide();
      app.modal.show(LoadingModal);
      window.location.reload();
    } catch (e) {
      this.loading = false;
      m.redraw();
    }
  }
}
