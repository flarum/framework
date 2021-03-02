import Button from '../../common/components/Button';
import Link from '../../common/components/Link';
import LinkButton from '../../common/components/LinkButton';
import Switch from '../../common/components/Switch';
import icon from '../../common/helpers/icon';
import punctuateSeries from '../../common/helpers/punctuateSeries';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import LoadingModal from './LoadingModal';
import ExtensionPermissionGrid from './ExtensionPermissionGrid';
import isExtensionEnabled from '../utils/isExtensionEnabled';
import AdminPage from './AdminPage';

export default class ExtensionPage extends AdminPage {
  oninit(vnode) {
    super.oninit(vnode);

    this.extension = app.data.extensions[this.attrs.id];
    this.changingState = false;

    this.infoFields = {
      discuss: 'fas fa-comment-alt',
      documentation: 'fas fa-book',
      support: 'fas fa-life-ring',
      website: 'fas fa-link',
      donate: 'fas fa-donate',
      source: 'fas fa-code',
    };

    if (!this.extension) {
      return m.route.set(app.route('dashboard'));
    }
  }

  className() {
    if (!this.extension) return '';

    return this.extension.id + '-Page';
  }

  view() {
    if (!this.extension) return null;

    return (
      <div className={'ExtensionPage ' + this.className()}>
        {this.header()}
        {!this.isEnabled() ? (
          <div className="container">
            <h3 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.enable_to_see')}</h3>
          </div>
        ) : (
          <div className="ExtensionPage-body">{this.sections().toArray()}</div>
        )}
      </div>
    );
  }

  header() {
    const isEnabled = this.isEnabled();

    return [
      <div className="ExtensionPage-header">
        <div className="container">
          <div className="ExtensionTitle">
            <span className="ExtensionIcon" style={this.extension.icon}>
              {this.extension.icon ? icon(this.extension.icon.name) : ''}
            </span>
            <div className="ExtensionName">
              <h2>{this.extension.extra['flarum-extension'].title}</h2>
            </div>
            <div className="ExtensionPage-headerTopItems">
              <ul>{listItems(this.topItems().toArray())}</ul>
            </div>
          </div>
          <div className="helpText">{this.extension.description}</div>
          <div className="ExtensionPage-headerItems">
            <Switch
              state={this.changingState ? !isEnabled : isEnabled}
              loading={this.changingState}
              onchange={this.toggle.bind(this, this.extension.id)}
            >
              {isEnabled ? app.translator.trans('core.admin.extension.enabled') : app.translator.trans('core.admin.extension.disabled')}
            </Switch>
            <aside className="ExtensionInfo">
              <ul>{listItems(this.infoItems().toArray())}</ul>
            </aside>
          </div>
        </div>
      </div>,
    ];
  }

  sections() {
    const items = new ItemList();

    items.add('content', this.content());

    items.add('permissions', [
      <div className="ExtensionPage-permissions">
        <div className="ExtensionPage-permissions-header">
          <div className="container">
            <h2 className="ExtensionTitle">{app.translator.trans('core.admin.extension.permissions_title')}</h2>
          </div>
        </div>
        <div className="container">
          {app.extensionData.extensionHasPermissions(this.extension.id) ? (
            ExtensionPermissionGrid.component({ extensionId: this.extension.id })
          ) : (
            <h3 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_permissions')}</h3>
          )}
        </div>
      </div>,
    ]);

    return items;
  }

  content() {
    const settings = app.extensionData.getSettings(this.extension.id);

    return (
      <div className="ExtensionPage-settings">
        <div className="container">
          {settings ? (
            <div className="Form">
              {settings.map(this.buildSettingComponent.bind(this))}
              <div className="Form-group">{this.submitButton()}</div>
            </div>
          ) : (
            <h3 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_settings')}</h3>
          )}
        </div>
      </div>
    );
  }

  topItems() {
    const items = new ItemList();

    items.add('version', <span className="ExtensionVersion">{this.extension.version}</span>);

    if (!this.isEnabled()) {
      const uninstall = () => {
        if (confirm(app.translator.trans('core.admin.extension.confirm_uninstall'))) {
          app
            .request({
              url: app.forum.attribute('apiUrl') + '/extensions/' + this.extension.id,
              method: 'DELETE',
            })
            .then(() => window.location.reload());

          app.modal.show(LoadingModal);
        }
      };

      items.add(
        'uninstall',
        <Button icon="fas fa-trash-alt" className="Button Button--primary" onclick={uninstall.bind(this)}>
          {app.translator.trans('core.admin.extension.uninstall_button')}
        </Button>
      );
    }

    return items;
  }

  infoItems() {
    const items = new ItemList();

    const links = this.extension.links;

    if (links.authors.length) {
      let authors = [];

      links.authors.map((author) => {
        authors.push(
          <Link href={author.link} external={true} target="_blank">
            {author.name}
          </Link>
        );
      });

      items.add('authors', [icon('fas fa-user'), <span>{punctuateSeries(authors)}</span>]);
    }

    Object.keys(this.infoFields).map((field) => {
      if (links[field]) {
        items.add(
          field,
          <LinkButton href={links[field]} icon={this.infoFields[field]} external={true} target="_blank">
            {app.translator.trans(`core.admin.extension.info_links.${field}`)}
          </LinkButton>
        );
      }
    });

    return items;
  }

  toggle() {
    const enabled = this.isEnabled();

    this.changingState = true;

    app
      .request({
        url: app.forum.attribute('apiUrl') + '/extensions/' + this.extension.id,
        method: 'PATCH',
        body: { enabled: !enabled },
        errorHandler: this.onerror.bind(this),
      })
      .then(() => {
        if (!enabled) localStorage.setItem('enabledExtension', this.extension.id);
        window.location.reload();
      });

    app.modal.show(LoadingModal);
  }

  isEnabled() {
    return isExtensionEnabled(this.extension.id);
  }

  onerror(e) {
    // We need to give the modal animation time to start; if we close the modal too early,
    // it breaks the bootstrap modal library.
    // TODO: This workaround should be removed when we move away from bootstrap JS for modals.
    setTimeout(() => {
      app.modal.close();
    }, 300); // Bootstrap's Modal.TRANSITION_DURATION is 300 ms.

    this.changingState = false;

    if (e.status !== 409) {
      throw e;
    }

    const error = e.response.errors[0];

    app.alerts.show(
      { type: 'error' },
      app.translator.trans(`core.lib.error.${error.code}_message`, {
        extension: error.extension,
        extensions: error.extensions.join(', '),
      })
    );
  }
}
