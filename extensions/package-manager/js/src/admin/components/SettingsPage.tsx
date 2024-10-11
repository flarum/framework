import type Mithril from 'mithril';
import app from 'flarum/admin/app';
import ExtensionPage, { ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import ItemList from 'flarum/common/utils/ItemList';

import QueueSection from './QueueSection';
import ControlSection from './ControlSection';
import ConfigureComposer from './ConfigureComposer';
import ConfigureAuth from './ConfigureAuth';
import DiscoverSection from './DiscoverSection';

export default class SettingsPage extends ExtensionPage {
  content() {
    const settings = app.registry.getSettings(this.extension.id);

    return (
      <div className="ExtensionPage-settings">
        <div className="container">
          {settings ? (
            [
              <div className="Form-group">
                <label>{app.translator.trans('flarum-extension-manager.admin.sections.settings.title')}</label>
                <div className="helpText">{app.translator.trans('flarum-extension-manager.admin.sections.settings.description')}</div>
              </div>,
              <div className="FormSectionGroup ExtensionManager-SettingsGroups">
                <div className="FormSection">
                  <label>{app.translator.trans('flarum-extension-manager.admin.settings.title')}</label>
                  <div className="Form">{settings.map(this.buildSettingComponent.bind(this))}</div>
                  <div className="Form-group Form--controls">{this.submitButton()}</div>
                </div>
                <ConfigureComposer buildSettingComponent={this.buildSettingComponent} />
                <ConfigureAuth buildSettingComponent={this.buildSettingComponent} />
              </div>,
            ]
          ) : (
            <h3 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_settings')}</h3>
          )}
        </div>
      </div>
    );
  }

  sections(vnode: Mithril.VnodeDOM<ExtensionPageAttrs, this>): ItemList<unknown> {
    const items = super.sections(vnode);

    items.add('discover', <DiscoverSection />, 15);

    items.add('control', <ControlSection />, 10);

    items.setPriority('content', 8);

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
