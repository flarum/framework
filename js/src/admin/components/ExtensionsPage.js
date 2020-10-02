import Page from '../../common/components/Page';
import Button from '../../common/components/Button';
import Dropdown from '../../common/components/Dropdown';
import AddExtensionModal from './AddExtensionModal';
import LoadingModal from './LoadingModal';
import ItemList from '../../common/utils/ItemList';
import icon from '../../common/helpers/icon';

export default class ExtensionsPage extends Page {
  view() {
    return (
      <div className="ExtensionsPage">
        <div className="ExtensionsPage-header">
          <div className="container">
            {Button.component(
              {
                icon: 'fas fa-plus',
                className: 'Button Button--primary',
                onclick: () => app.modal.show(AddExtensionModal),
              },
              app.translator.trans('core.admin.extensions.add_button')
            )}
          </div>
        </div>

        <div className="ExtensionsPage-list">
          <div className="container">
            <ul className="ExtensionList">
              {Object.keys(app.data.extensions).map((id) => {
                const extension = app.data.extensions[id];
                const controls = this.controlItems(extension.id).toArray();

                return (
                  <li className={'ExtensionListItem ' + (!this.isEnabled(extension.id) ? 'disabled' : '')}>
                    <div className="ExtensionListItem-content">
                      <span className="ExtensionListItem-icon ExtensionIcon" style={extension.icon}>
                        {extension.icon ? icon(extension.icon.name) : ''}
                      </span>
                      {controls.length ? (
                        <Dropdown
                          className="ExtensionListItem-controls"
                          buttonClassName="Button Button--icon Button--flat"
                          menuClassName="Dropdown-menu--right"
                          icon="fas fa-ellipsis-h"
                        >
                          {controls}
                        </Dropdown>
                      ) : (
                        ''
                      )}
                      <div className="ExtensionListItem-main">
                        <label className="ExtensionListItem-title">
                          <input type="checkbox" checked={this.isEnabled(extension.id)} onclick={this.toggle.bind(this, extension.id)} />{' '}
                          {extension.extra['flarum-extension'].title}
                        </label>
                        <div className="ExtensionListItem-version">{extension.version}</div>
                        <div className="ExtensionListItem-description">{extension.description}</div>
                      </div>
                    </div>
                  </li>
                );
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
      items.add(
        'settings',
        Button.component(
          {
            icon: 'fas fa-cog',
            onclick: app.extensionSettings[name],
          },
          app.translator.trans('core.admin.extensions.settings_button')
        )
      );
    }

    if (!enabled) {
      items.add(
        'uninstall',
        Button.component(
          {
            icon: 'far fa-trash-alt',
            onclick: () => {
              app
                .request({
                  url: app.forum.attribute('apiUrl') + '/extensions/' + name,
                  method: 'DELETE',
                })
                .then(() => window.location.reload());

              app.modal.show(LoadingModal);
            },
          },
          app.translator.trans('core.admin.extensions.uninstall_button')
        )
      );
    }

    return items;
  }

  isEnabled(name) {
    const enabled = JSON.parse(app.data.settings.extensions_enabled);

    return enabled.indexOf(name) !== -1;
  }

  toggle(id) {
    app.modal.show(LoadingModal);

    const enabled = this.isEnabled(id);

    app
      .request({
        url: app.forum.attribute('apiUrl') + '/extensions/' + id,
        method: 'PATCH',
        body: { enabled: !enabled },
        errorHandler: this.onerror.bind(this),
      })
      .then(() => {
        if (!enabled) localStorage.setItem('enabledExtension', id);
        window.location.reload();
      });
  }

  onerror(e) {
    // We need to give the modal animation time to start; if we close the modal too early,
    // it breaks the bootstrap modal library.
    // TODO: This workaround should be removed when we move away from bootstrap JS for modals.
    setTimeout(() => {
      app.modal.close();

      const error = JSON.parse(e.responseText).errors[0];

      app.alerts.show(
        { type: 'error' },
        app.translator.trans(`core.lib.error.${error.code}_message`, {
          extension: error.extension,
          extensions: error.extensions.join(', '),
        })
      );
    }, 250);
  }
}
