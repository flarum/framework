import type Mithril from 'mithril';
import app from 'flarum/admin/app';
import ExtensionPage, { ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import ItemList from 'flarum/common/utils/ItemList';

import QueueSection from './QueueSection';
import ControlSection from './ControlSection';
import ConfigureComposer from './ConfigureComposer';
import Alert from 'flarum/common/components/Alert';
import listItems from 'flarum/common/helpers/listItems';

export default class SettingsPage extends ExtensionPage {
  content() {
    const settings = app.extensionData.getSettings(this.extension.id);

    const warnings = [app.translator.trans('flarum-package-manager.admin.settings.access_warning')];

    if (app.data.debugEnabled) warnings.push(app.translator.trans('flarum-package-manager.admin.settings.debug_mode_warning'));

    return (
      <div className="ExtensionPage-settings">
        <div className="container">
          <div className="Form-group">
            <Alert className="PackageManager-primaryWarning" type="warning" dismissible={false}>
              <ul>{listItems(warnings)}</ul>
            </Alert>
          </div>
          {settings ? (
            <div className="SettingsGroups">
              <div className="Form">
                {settings.map(this.buildSettingComponent.bind(this))}
                <div className="Form-group">{this.submitButton()}</div>
              </div>
              <ConfigureComposer buildSettingComponent={this.buildSettingComponent} />
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

    if (app.data.settings['flarum-package-manager.queue_jobs'] !== '0' && app.data.settings['flarum-package-manager.queue_jobs']) {
      items.add('queue', <QueueSection />, 5);
    }

    items.setPriority('permissions', 0);

    return items;
  }

  onsaved() {
    super.onsaved();
    m.redraw();
  }
}
