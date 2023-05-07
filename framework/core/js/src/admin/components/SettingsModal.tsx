import app from '../../admin/app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Button from '../../common/components/Button';
import Stream from '../../common/utils/Stream';
import saveSettings from '../utils/saveSettings';
import Mithril from 'mithril';
import { MutableSettings, SettingValue } from './AdminPage';

export interface ISettingsModalAttrs extends IInternalModalAttrs {}

export default abstract class SettingsModal<CustomAttrs extends ISettingsModalAttrs = ISettingsModalAttrs> extends Modal<CustomAttrs> {
  settings: MutableSettings = {};
  loading: boolean = false;

  form(): Mithril.Children {
    return null;
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          {this.form()}

          <div className="Form-group">{this.submitButton()}</div>
        </div>
      </div>
    );
  }

  submitButton(): Mithril.Children {
    return (
      <Button type="submit" className="Button Button--primary" loading={this.loading} disabled={!this.changed()}>
        {app.translator.trans('core.admin.settings.submit_button')}
      </Button>
    );
  }

  setting(key: string, fallback: string = ''): Stream<SettingValue> {
    this.settings[key] = this.settings[key] || Stream(app.data.settings[key] || fallback);

    return this.settings[key];
  }

  dirty() {
    const dirty: Record<string, SettingValue> = {};

    Object.keys(this.settings).forEach((key) => {
      const value = this.settings[key]();

      if (value !== app.data.settings[key]) {
        dirty[key] = value;
      }
    });

    return dirty;
  }

  changed() {
    return Object.keys(this.dirty()).length;
  }

  onsubmit(e: SubmitEvent) {
    e.preventDefault();

    this.loading = true;

    saveSettings(this.dirty()).then(this.onsaved.bind(this), this.loaded.bind(this));
  }

  onsaved() {
    this.hide();
  }
}
