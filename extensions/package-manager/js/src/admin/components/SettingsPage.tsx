import type Mithril from 'mithril';
import app from 'flarum/admin/app';
import ExtensionPage, { ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import ItemList from 'flarum/common/utils/ItemList';

import QueueSection from './QueueSection';
import ControlSection from './ControlSection';
import ConfigureComposer from './ConfigureComposer';
import Alert from 'flarum/common/components/Alert';
import listItems from 'flarum/common/helpers/listItems';
import ConfigureAuth from './ConfigureAuth';

export default class SettingsPage extends ExtensionPage {
  content() {
    const settings = app.extensionData.getSettings(this.extension.id);

    const warnings = [app.translator.trans('flarum-extension-manager.admin.settings.access_warning')];

    if (app.data.debugEnabled) warnings.push(app.translator.trans('flarum-extension-manager.admin.settings.debug_mode_warning'));

    return (
      <div className="ExtensionPage-settings">
        <div className="container">
          <div className="Form-group">
            <Alert className="ExtensionManager-primaryWarning" type="warning" dismissible={false}>
              <ul>{listItems(warnings)}</ul>
            </Alert>
          </div>
          {settings ? (
            <div className="ExtensionManager-SettingsGroups">
              <div className="Form">
                <label>{app.translator.trans('flarum-extension-manager.admin.settings.title')}</label>
                <div className="SettingsGroups-content">{settings.map(this.buildSettingComponent.bind(this))}</div>
                <div className="Form-group Form--controls">{this.submitButton()}</div>
              </div>
              <ConfigureComposer buildSettingComponent={this.buildSettingComponent} />
              <ConfigureAuth buildSettingComponent={this.buildSettingComponent} />
            </div>
          ) : (
            <h3 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_settings')}</h3>
          )}
        </div>
      </div>
    );
  }

  sections(vnode: Mithril.VnodeDOM<ExtensionPageAttrs, this>): ItemList<unknown> {
    const items = super.sections(vnode);

    items.setPriority('content', 10);

    items.add('control', <ControlSection />, 8);

    if (app.data.settings['flarum-extension-manager.queue_jobs'] !== '0' && app.data.settings['flarum-extension-manager.queue_jobs']) {
      items.add('queue', <QueueSection />, 5);
    }

    items.remove('permissions');

    return items;
  }

  onsaved() {
    super.onsaved();
    m.redraw();
  }
}
