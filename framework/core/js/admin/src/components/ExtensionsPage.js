import Component from 'flarum/Component';
import LinkButton from 'flarum/components/LinkButton';
import Button from 'flarum/components/Button';
import Dropdown from 'flarum/components/Dropdown';
import Separator from 'flarum/components/Separator';
import AddExtensionModal from 'flarum/components/AddExtensionModal';
import LoadingModal from 'flarum/components/LoadingModal';
import ItemList from 'flarum/utils/ItemList';
import icon from 'flarum/helpers/icon';

export default class ExtensionsPage extends Component {
  view() {
    return (
      <div className="ExtensionsPage">
        <div className="ExtensionsPage-header">
          <div className="container">
            {Button.component({
              children: 'Add Extension',
              icon: 'plus',
              className: 'Button Button--primary',
              onclick: () => app.modal.show(new AddExtensionModal())
            })}
          </div>
        </div>

        <div className="ExtensionsPage-list">
          <div className="container">
            <ul className="ExtensionList">
              {app.extensions
                .sort((a, b) => a.name.localeCompare(b.name))
                .map(extension => (
                  <li className={'ExtensionListItem ' + (!this.isEnabled(extension.name) ? 'disabled' : '')}>
                    {Dropdown.component({
                      icon: 'ellipsis-v',
                      children: this.controlItems(extension).toArray(),
                      className: 'ExtensionListItem-controls',
                      buttonClassName: 'Button Button--icon Button--flat',
                      menuClassName: 'Dropdown-menu--right'
                    })}
                    <div className="ExtensionListItem-content">
                      <span className="ExtensionListItem-icon ExtensionIcon" style={extension.icon}>
                        {extension.icon ? icon(extension.icon.name) : ''}
                      </span>
                      <h4 className="ExtensionListItem-title">
                        {extension.title}{' '}
                        <small className="ExtensionListItem-version">{extension.version}</small>
                      </h4>
                      <div className="ExtensionListItem-description">{extension.description}</div>
                    </div>
                  </li>
                ))}
            </ul>
          </div>
        </div>
      </div>
    );
  }

  controlItems(extension) {
    const items = new ItemList();
    const enabled = this.isEnabled(extension.name);

    if (app.extensionSettings[extension.name]) {
      items.add('settings', Button.component({
        icon: 'cog',
        children: 'Settings',
        onclick: app.extensionSettings[extension.name]
      }));
    }

    items.add('toggle', Button.component({
      icon: 'power-off',
      children: enabled ? 'Disable' : 'Enable',
      onclick: () => {
        app.request({
          url: app.forum.attribute('apiUrl') + '/extensions/' + extension.name,
          method: 'PATCH',
          data: {enabled: !enabled}
        }).then(() => window.location.reload());

        app.modal.show(new LoadingModal());
      }
    }));

    if (!enabled) {
      items.add('uninstall', Button.component({
        icon: 'trash-o',
        children: 'Uninstall',
        onclick: () => {
          app.request({
            url: app.forum.attribute('apiUrl') + '/extensions/' + extension.name,
            method: 'DELETE',
          }).then(() => window.location.reload());

          app.modal.show(new LoadingModal());
        }
      }));
    }

    // items.add('separator2', Separator.component());

    // items.add('support', LinkButton.component({
    //   icon: 'support',
    //   children: 'Support'
    // }));

    return items;
  }

  isEnabled(name) {
    const enabled = JSON.parse(app.config.extensions_enabled);

    return enabled.indexOf(name) !== -1;
  }
}
