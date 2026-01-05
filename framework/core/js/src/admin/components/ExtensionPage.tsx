import app from '../../admin/app';
import Button from '../../common/components/Button';
import Link from '../../common/components/Link';
import LinkButton from '../../common/components/LinkButton';
import Switch from '../../common/components/Switch';
import punctuateSeries from '../../common/helpers/punctuateSeries';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import LoadingModal from './LoadingModal';
import ExtensionPermissionGrid from './ExtensionPermissionGrid';
import isExtensionEnabled from '../utils/isExtensionEnabled';
import AdminPage from './AdminPage';
import ReadmeModal from './ReadmeModal';
import RequestError from '../../common/utils/RequestError';
import { Extension } from '../AdminApplication';
import { IPageAttrs } from '../../common/components/Page';
import type Mithril from 'mithril';
import extractText from '../../common/utils/extractText';
import Form from '../../common/components/Form';
import Icon from '../../common/components/Icon';
import { MaintenanceMode } from '../../common/Application';
import InfoTile from '../../common/components/InfoTile';
import Alert from '../../common/components/Alert';

export interface ExtensionPageAttrs extends IPageAttrs {
  id: string;
}

export default class ExtensionPage<Attrs extends ExtensionPageAttrs = ExtensionPageAttrs> extends AdminPage<Attrs> {
  extension!: Extension;

  changingState = false;

  infoFields = {
    discuss: 'fas fa-comment-alt',
    documentation: 'fas fa-book',
    support: 'fas fa-life-ring',
    website: 'fas fa-link',
    donate: 'fas fa-donate',
    source: 'fas fa-code',
  };

  oninit(vnode: Mithril.Vnode<Attrs, this>) {
    super.oninit(vnode);

    const extension = app.data.extensions[this.attrs.id];

    if (!extension) {
      return m.route.set(app.route('dashboard'));
    }

    this.extension = extension;
  }

  className() {
    if (!this.extension) return '';

    return this.extension.id + '-Page';
  }

  view(vnode: Mithril.VnodeDOM<Attrs, this>) {
    if (!this.extension) return null;

    const isAbandoned = this.extension.abandoned;
    const hasReplacement = typeof isAbandoned === 'string';

    return (
      <div className={'ExtensionPage ' + this.className()}>
        {this.header()}
        {isAbandoned && (
          <div className="container">
            <div className={`Alert Alert--${hasReplacement ? 'danger' : 'warning'}`}>
              <span className="Alert-body">
                {hasReplacement
                  ? app.translator.trans('core.admin.extension.abandoned_with_replacement', { replacement: isAbandoned })
                  : app.translator.trans('core.admin.extension.abandoned_no_replacement')}
              </span>
            </div>
          </div>
        )}
        {app.data.maintenanceMode === MaintenanceMode.SAFE_MODE && !app.data.safeModeExtensions?.includes(this.extension.id) ? (
          <div className="container">
            <div className="ExtensionPage-body">
              <InfoTile icon="fas fa-exclamation-triangle" type="warning">
                {app.translator.trans('core.admin.extension.safe_mode_warning')}
              </InfoTile>
            </div>
          </div>
        ) : (
          this.body(vnode)
        )}
      </div>
    );
  }

  body(vnode: Mithril.VnodeDOM<Attrs, this>) {
    const supportsDbDriver =
      !this.extension.extra['flarum-extension']['database-support'] ||
      this.extension.extra['flarum-extension']['database-support'].map((driver) => driver.toLowerCase()).includes(app.data.dbDriver.toLowerCase());

    return this.isEnabled() ? (
      <div className="ExtensionPage-body">
        {!supportsDbDriver && (
          <Alert type="error" dismissible={false}>
            {app.translator.trans('core.admin.extension.database_driver_mismatch')}
          </Alert>
        )}
        {this.sections(vnode).toArray()}
      </div>
    ) : (
      <div className="container">
        <h3 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.enable_to_see')}</h3>
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
              {!!this.extension.icon && <Icon name={this.extension.icon.name} />}
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

  sections(vnode: Mithril.VnodeDOM<Attrs, this>) {
    const items = new ItemList();

    items.add('content', this.content(vnode));

    items.add(
      'permissions',
      <div className="ExtensionPage-permissions">
        <div className="ExtensionPage-permissions-header">
          <div className="container">
            <h2 className="ExtensionTitle">{app.translator.trans('core.admin.extension.permissions_title')}</h2>
          </div>
        </div>
        <div className="container">
          {app.registry.extensionHasPermissions(this.extension.id) ? (
            <ExtensionPermissionGrid extensionId={this.extension.id} />
          ) : (
            <h3 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_permissions')}</h3>
          )}
        </div>
      </div>
    );

    return items;
  }

  content(vnode: Mithril.VnodeDOM<Attrs, this>) {
    const settings = app.registry.getSettings(this.extension.id);

    return (
      <div className="ExtensionPage-settings">
        <div className="container">
          {settings ? (
            <Form>
              {settings.map(this.buildSettingComponent.bind(this))}
              <div className="Form-group Form-controls">{this.submitButton()}</div>
            </Form>
          ) : (
            <h3 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_settings')}</h3>
          )}
        </div>
      </div>
    );
  }

  topItems() {
    const items = new ItemList<Mithril.Children>();

    items.add('version', <span className="ExtensionVersion">{this.extension.version}</span>);

    if (!this.isEnabled()) {
      const purge = () => {
        if (confirm(extractText(app.translator.trans('core.admin.extension.confirm_purge')))) {
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
        <Button icon="fas fa-trash-alt" className="Button Button--primary" onclick={purge.bind(this)}>
          {app.translator.trans('core.admin.extension.purge_button')}
        </Button>
      );
    }

    return items;
  }

  infoItems() {
    const items = new ItemList<Mithril.Children>();

    const links = this.extension.links;

    if (links.authors?.length) {
      const authors = links.authors.map((author) => (
        <Link href={author.link} external={true} target="_blank">
          {author.name}
        </Link>
      ));

      items.add('authors', [<Icon name={'fas fa-user'} />, <span>{punctuateSeries(authors)}</span>]);
    }

    (Object.keys(this.infoFields) as (keyof ExtensionPage['infoFields'])[]).map((field) => {
      if (links[field]) {
        items.add(
          field,
          <LinkButton href={links[field]} icon={this.infoFields[field]} external={true} target="_blank">
            {app.translator.trans(`core.admin.extension.info_links.${field}`)}
          </LinkButton>
        );
      }
    });

    let supportedDatabases = this.extension.extra['flarum-extension']['database-support'] ?? null;
    if (supportedDatabases && supportedDatabases.length) {
      supportedDatabases = supportedDatabases.map((database: string) => {
        return (
          {
            mysql: 'MySQL',
            sqlite: 'SQLite',
            pgsql: 'PostgreSQL',
          }[database] || database
        );
      });

      items.add(
        'database-support',
        <span className="LinkButton">
          <Icon name="fas fa-database" />
          {supportedDatabases.join(', ')}
        </span>
      );
    }

    const extension = this.extension;
    items.add(
      'readme',
      <Button icon="fab fa-readme" className="Button Button--text" onclick={() => app.modal.show(ReadmeModal, { extension })}>
        {app.translator.trans('core.admin.extension.readme.button_label')}
      </Button>,
      10
    );

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
      })
      .catch(() => {});

    app.modal.show(LoadingModal);
  }

  isEnabled() {
    return isExtensionEnabled(this.extension.id);
  }

  onerror(e: RequestError): void | false {
    app.modal.close();

    this.changingState = false;

    // If it's not a conflict error, use the default request error handler.
    if (e.status !== 409) {
      return false;
    }

    const error = e.response?.errors?.[0];

    if (error) {
      app.alerts.show(
        { type: 'error' },
        app.translator.trans(`core.lib.error.${error.code}_message`, {
          extension: error.extension,
          extensions: (error.extensions as string[])?.join(', '),
        })
      );
    }
  }
}
