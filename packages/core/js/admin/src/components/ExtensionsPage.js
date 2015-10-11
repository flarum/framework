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
              {Object.keys(app.extensions)
                .sort((a, b) => app.extensions[a].extra['flarum-extension'].title.localeCompare(app.extensions[b].extra['flarum-extension'].title))
                .map(name => {
                  const extension = app.extensions[name];

                  return <li className={'ExtensionListItem ' + (!this.isEnabled(name) ? 'disabled' : '')}>
                    {Dropdown.component({
                      icon: 'ellipsis-v',
                      children: this.controlItems(name).toArray(),
                      className: 'ExtensionListItem-controls',
                      buttonClassName: 'Button Button--icon Button--flat',
                      menuClassName: 'Dropdown-menu--right'
                    })}
                    <div className="ExtensionListItem-content">
                      <span className="ExtensionListItem-icon ExtensionIcon" style={extension.extra['flarum-extension'].icon}>
                        {extension.extra['flarum-extension'].icon ? icon(extension.extra['flarum-extension'].icon.name) : ''}
                      </span>
                      <h4 className="ExtensionListItem-title">
                        {extension.extra['flarum-extension'].title} {' '}
                        <small className="ExtensionListItem-version">{extension.version}</small>
                      </h4>
                      <div className="ExtensionListItem-description">{extension.description}</div>
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
    const extension = app.extensions[name];
    const enabled = this.isEnabled(name);

    items.add('info', <span>
      Package Name: {extension.name}<br/>
      Installed in: {name}
    </span>);

    if (app.extensionSettings[name]) {
      items.add('settings', Button.component({
        icon: 'cog',
        children: 'Settings',
        onclick: app.extensionSettings[name]
      }));
    }

    items.add('toggle', Button.component({
      icon: 'power-off',
      children: enabled ? 'Disable' : 'Enable',
      onclick: () => {
        app.request({
          url: app.forum.attribute('apiUrl') + '/extensions/' + name,
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
            url: app.forum.attribute('apiUrl') + '/extensions/' + name,
            method: 'DELETE'
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
    const enabled = JSON.parse(app.settings.extensions_enabled);

    return enabled.indexOf(name) !== -1;
  }
}
