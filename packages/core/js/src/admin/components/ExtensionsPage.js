import Page from './Page';
import LinkButton from '../../common/components/LinkButton';
import Button from '../../common/components/Button';
import Dropdown from '../../common/components/Dropdown';
import Separator from '../../common/components/Separator';
import AddExtensionModal from './AddExtensionModal';
import LoadingModal from './LoadingModal';
import ItemList from '../../common/utils/ItemList';
import icon from '../../common/helpers/icon';
import listItems from '../../common/helpers/listItems';

export default class ExtensionsPage extends Page {
  view() {
    return (
      <div className="ExtensionsPage">
        <div className="ExtensionsPage-header">
          <div className="container">
            {Button.component({
              children: app.translator.trans('core.admin.extensions.add_button'),
              icon: 'fas fa-plus',
              className: 'Button Button--primary',
              onclick: () => app.modal.show(new AddExtensionModal())
            })}
          </div>
        </div>

        <div className="ExtensionsPage-list">
          <div className="container">
            <ul className="ExtensionList">
              {Object.keys(app.data.extensions)
                .map(id => {
                  const extension = app.data.extensions[id];
                  const controls = this.controlItems(extension.id).toArray();

                  return <li className={'ExtensionListItem ' + (!this.isEnabled(extension.id) ? 'disabled' : '')}>
                    <div className="ExtensionListItem-content">
                      <span className="ExtensionListItem-icon ExtensionIcon" style={extension.icon}>
                        {extension.icon ? icon(extension.icon.name) : ''}
                      </span>
                      {controls.length ? (
                        <Dropdown
                          className="ExtensionListItem-controls"
                          buttonClassName="Button Button--icon Button--flat"
                          menuClassName="Dropdown-menu--right"
                          icon="fas fa-ellipsis-h">
                          {controls}
                        </Dropdown>
                      ) : ''}
                      <div className="ExtensionListItem-main">
                        <label className="ExtensionListItem-title">
                          <input type="checkbox" checked={this.isEnabled(extension.id)} onclick={this.toggle.bind(this, extension.id)}/> {' '}
                            {extension.extra['flarum-extension'].title}
                        </label>
                        <div className="ExtensionListItem-version">{extension.version}</div>
                        <div className="ExtensionListItem-description">{extension.description}</div>
                      </div>
                    </div>
                  </li>;
                })}
            </ul>
          </div>
        </div>
      </div>
    );
  }

  controlItems(name) {
    const items = new ItemList();
    const enabled = this.isEnabled(name);

    if (app.extensionSettings[name]) {
      items.add('settings', Button.component({
        icon: 'fas fa-cog',
        children: app.translator.trans('core.admin.extensions.settings_button'),
        onclick: app.extensionSettings[name]
      }));
    }

    if (!enabled) {
      items.add('uninstall', Button.component({
        icon: 'far fa-trash-alt',
        children: app.translator.trans('core.admin.extensions.uninstall_button'),
        onclick: () => {
          app.request({
            url: app.forum.attribute('apiUrl') + '/extensions/' + name,
            method: 'DELETE'
          }).then(() => window.location.reload());

          app.modal.show(new LoadingModal());
        }
      }));
    }

    return items;
  }

  isEnabled(name) {
    const enabled = JSON.parse(app.data.settings.extensions_enabled);

    return enabled.indexOf(name) !== -1;
  }

  toggle(id) {
    const enabled = this.isEnabled(id);

    app.request({
      url: app.forum.attribute('apiUrl') + '/extensions/' + id,
      method: 'PATCH',
      data: {enabled: !enabled}
    }).then(() => {
      if (!enabled) localStorage.setItem('enabledExtension', id);
      window.location.reload();
    });

    app.modal.show(new LoadingModal());
  }
}
