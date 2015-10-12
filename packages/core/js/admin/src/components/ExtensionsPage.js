import Component from 'flarum/Component';
import LinkButton from 'flarum/components/LinkButton';
import Button from 'flarum/components/Button';
import Dropdown from 'flarum/components/Dropdown';
import Separator from 'flarum/components/Separator';
import AddExtensionModal from 'flarum/components/AddExtensionModal';
import LoadingModal from 'flarum/components/LoadingModal';
import ItemList from 'flarum/utils/ItemList';
import icon from 'flarum/helpers/icon';
import listItems from 'flarum/helpers/listItems';

export default class ExtensionsPage extends Component {
  view() {
    const groups = [
      {keyword: 'discussion', title: 'Discussions', extensions: []},
      {keyword: 'formatting', title: 'Formatting', extensions: []},
      {keyword: 'moderation', title: 'Moderation', extensions: []},
      {keyword: 'theme', title: 'Themes', extensions: []},
      {keyword: 'authentication', title: 'Authentication', extensions: []},
      {keyword: 'locale', title: 'Language Packs', extensions: []},
      {title: 'Other', extensions: []}
    ];

    Object.keys(app.extensions).forEach(id => {
      const extension = app.extensions[id];
      const keywords = extension.keywords;

      const grouped = keywords && groups.some(group => {
        if (keywords.indexOf(group.keyword) !== -1) {
          group.extensions.push(extension);
          return true;
        }
      });

      if (!grouped) {
        groups[groups.length - 1].extensions.push(extension);
      }
    });

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
            {groups.filter(group => group.extensions.length).map(group => (
              <div className="ExtensionGroup">
                <h3>{group.title}</h3>
                <ul className="ExtensionList">
                  {group.extensions
                    .sort((a, b) => a.extra['flarum-extension'].title.localeCompare(b.extra['flarum-extension'].title))
                    .map(extension => {
                      return <li className={'ExtensionListItem ' + (!this.isEnabled(extension.id) ? 'disabled' : '')}>
                        <ul className="ExtensionListItem-controls" style={extension.extra['flarum-extension'].icon}>
                          {listItems(this.controlItems(extension.id).toArray())}
                        </ul>
                        <div className="ExtensionListItem-content">
                          <span className="ExtensionListItem-icon ExtensionIcon" style={extension.extra['flarum-extension'].icon}>
                            {extension.extra['flarum-extension'].icon ? icon(extension.extra['flarum-extension'].icon.name) : ''}
                          </span>
                          <h4 className="ExtensionListItem-title" title={extension.description}>
                            {extension.extra['flarum-extension'].title}
                          </h4>
                          <div className="ExtensionListItem-version">{extension.version}</div>
                        </div>
                      </li>;
                    })}
                </ul>
              </div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  controlItems(name) {
    const items = new ItemList();
    const extension = app.extensions[name];
    const enabled = this.isEnabled(name);

    if (app.extensionSettings[name]) {
      items.add('settings', Button.component({
        icon: 'cog',
        className: 'Button',
        children: 'Settings',
        onclick: app.extensionSettings[name]
      }));
    }

    items.add('toggle', Button.component({
      icon: 'power-off',
      className: 'Button',
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
        className: 'Button',
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

    return items;
  }

  isEnabled(name) {
    const enabled = JSON.parse(app.settings.extensions_enabled);

    return enabled.indexOf(name) !== -1;
  }
}
