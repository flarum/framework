import app from '../../admin/app';
import Component, { type ComponentAttrs } from '../../common/Component';
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
import Icon from '../../common/components/Icon';
import { MaintenanceMode } from '../../common/Application';
import InfoTile from '../../common/components/InfoTile';
import Alert from '../../common/components/Alert';
import Tabs, { type TabItem } from '../../common/components/Tabs';
import type { SettingsTabConfig } from '../utils/AdminRegistry';

/**
 * Renders whatever its `render` attr returns, when it is rendered.
 */
class RenderSlot extends Component<ComponentAttrs & { render: () => Mithril.Children }> {
  view() {
    return this.attrs.render();
  }
}

// Module functions rather than methods, so a custom page's own methods are never called in their place.
function permissionsGrid(extensionId: string): Mithril.Children {
  return app.registry.extensionHasPermissions(extensionId) ? (
    <ExtensionPermissionGrid extensionId={extensionId} />
  ) : (
    <h3 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_permissions')}</h3>
  );
}

function permissionsBlock(extensionId: string): Mithril.Children {
  return (
    <div className="ExtensionPage-permissions">
      <div className="ExtensionPage-permissions-header">
        <div className="container">
          <h2 className="ExtensionTitle">{app.translator.trans('core.admin.extension.permissions_title')}</h2>
        </div>
      </div>
      <div className="container">{permissionsGrid(extensionId)}</div>
    </div>
  );
}

export interface ExtensionPageAttrs extends IPageAttrs {
  id: string;
  /** The settings tab to open, from the `?tab=` query parameter. */
  tab?: string;
}

export default class ExtensionPage<Attrs extends ExtensionPageAttrs = ExtensionPageAttrs> extends AdminPage<Attrs> {
  extension!: Extension;

  changingState = false;

  /**
   * The open settings tab, when the page has tabs. Kept in the URL as `?tab=`.
   */
  activePageTab: string | null = null;

  /** The last `?tab=` this page was routed with. */
  protected requestedTab?: string;

  infoFields = {
    discuss: 'fas fa-comment-alt',
    documentation: 'fas fa-book',
    support: 'fas fa-life-ring',
    website: 'fas fa-link',
    donate: 'fas fa-donate',
    source: 'fas fa-code',
  };

  /**
   * Whether the Permissions tab is shown. Off when another extension removed or
   * replaced the `permissions` item of `sections()`.
   */
  protected permissionsTabShown = true;

  oninit(vnode: Mithril.Vnode<Attrs, this>) {
    super.oninit(vnode);

    const extension = app.data.extensions[this.attrs.id];

    if (!extension) {
      return m.route.set(app.route('dashboard'));
    }

    this.extension = extension;
    this.activePageTab = this.attrs.tab || null;
    this.requestedTab = this.attrs.tab;
  }

  onbeforeupdate(vnode: Mithril.VnodeDOM<Attrs, this>) {
    super.onbeforeupdate(vnode);

    // A link to another tab of this page, such as a search result, updates the page instead of recreating it.
    if (vnode.attrs.tab && vnode.attrs.tab !== this.requestedTab) {
      this.activePageTab = vnode.attrs.tab;
    }

    this.requestedTab = vnode.attrs.tab;
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
              // toggle() takes no arguments, but subclasses still receive the bound id.
              onchange={(this.toggle as (...args: unknown[]) => void).bind(this, this.extension.id)}
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
    const items = new ItemList<Mithril.Children>();

    if (!this.usesPageTabs()) {
      items.add('content', this.content(vnode));
      items.add('permissions', permissionsBlock(this.extension.id));

      return items;
    }

    // Both slots render after other extensions have changed this list, so they can
    // respect what was done to it: replacing `content` brings back the permissions
    // block below it, and removing or replacing `permissions` drops the Permissions tab.
    const content: Mithril.Children = (
      <RenderSlot
        render={() => {
          this.permissionsTabShown = items.has('permissions') && items.get('permissions') === permissions;

          return this.pageTabs(vnode, this.pageTabItems().toArray(true));
        }}
      />
    );
    const permissions: Mithril.Children = (
      <RenderSlot render={() => (items.has('content') && items.get('content') === content ? null : permissionsBlock(this.extension.id))} />
    );

    items.add('content', content);
    items.add('permissions', permissions);

    return items;
  }

  /**
   * Whether the page is laid out in tabs. Custom pages that replace `content()`
   * keep the stacked layout they were written for until they opt in by
   * overriding `pageTabItems()`.
   */
  usesPageTabs(): boolean {
    return !this.subclassDefines('content') || this.subclassDefines('pageTabItems');
  }

  /**
   * Whether a subclass of ExtensionPage defines the method itself.
   * Patches applied to ExtensionPage.prototype with `extend()` don't count.
   */
  protected subclassDefines(method: string): boolean {
    for (let proto = Object.getPrototypeOf(this); proto && proto !== ExtensionPage.prototype; proto = Object.getPrototypeOf(proto)) {
      if (Object.prototype.hasOwnProperty.call(proto, method)) return true;
    }

    return false;
  }

  /**
   * The page's tabs: the registered settings tabs, or a General tab when the
   * extension registered none, then Permissions. Tabs with nothing in them are left out.
   *
   * A custom page can add its own tabs here and render them in `pageTabContent()`.
   */
  pageTabItems(): ItemList<TabItem> {
    const items = new ItemList<TabItem>();
    const unsavedTabs = this.unsavedTabs();
    const tabs = this.settingsTabs();

    tabs.forEach((tab, index) => {
      if (!this.settingsGroups(tab.id).length) return;

      const unsaved = unsavedTabs.has(tab.id);

      items.add(
        tab.id,
        {
          id: tab.id,
          label: tab.label,
          icon: tab.icon,
          indicator: unsaved,
          indicatorLabel: unsaved ? extractText(app.translator.trans('core.admin.extension.unsaved_changes')) : undefined,
        },
        (tabs.length - index) * 10
      );
    });

    if (this.permissionsTabShown && app.registry.extensionHasPermissions(this.extension.id)) {
      items.add(
        'permissions',
        {
          id: 'permissions',
          label: app.translator.trans('core.admin.extension.permissions_title'),
          icon: 'fas fa-key',
        },
        -100
      );
    }

    return items;
  }

  /**
   * The registered settings tabs, or the default General tab.
   */
  settingsTabs(): SettingsTabConfig[] {
    const registered = app.registry.getTabs(this.extension.id);

    if (registered.length) return registered;

    return [
      {
        id: AdminPage.DEFAULT_TAB,
        label: app.translator.trans('core.admin.extension.general_tab'),
        icon: 'fas fa-sliders-h',
      },
    ];
  }

  /**
   * Renders the tabs. A lone tab is shown without the tab bar.
   */
  pageTabs(vnode: Mithril.VnodeDOM<Attrs, this>, tabs: TabItem[]): Mithril.Children {
    if (!tabs.length) {
      return (
        <div className="container">
          <h3 className="ExtensionPage-subHeader ExtensionPage-empty">{app.translator.trans('core.admin.extension.no_settings_or_permissions')}</h3>
        </div>
      );
    }

    if (tabs.length === 1) {
      if (tabs[0].id === 'permissions') return permissionsBlock(this.extension.id);

      return this.usesDefaultContent(tabs[0].id) ? (
        this.content(vnode)
      ) : (
        <div className="ExtensionPage-settings">
          <div className="container">{this.pageTabContent(tabs[0].id, vnode)}</div>
        </div>
      );
    }

    const active = tabs.find((tab) => tab.id === this.activePageTab) ? this.activePageTab! : tabs[0].id;

    return (
      <div className="ExtensionPage-tabs">
        <div className="container">
          <Tabs tabs={tabs} active={active} idPrefix={`${this.extension.id}-settings`} onchange={this.changePageTab.bind(this)}>
            {this.pageTabContent(active, vnode)}
          </Tabs>
        </div>
      </div>
    );
  }

  /**
   * The content of one tab. Override to render tabs added in `pageTabItems()`.
   */
  pageTabContent(tab: string, vnode: Mithril.VnodeDOM<Attrs, this>): Mithril.Children {
    if (tab === 'permissions') {
      return <div className="ExtensionPage-permissions">{permissionsGrid(this.extension.id)}</div>;
    }

    // `content()` renders the General tab, so changes other extensions make to it still show.
    if (this.usesDefaultContent(tab)) return this.content(vnode);

    return <div className="ExtensionPage-settings">{this.settingsForm(tab)}</div>;
  }

  /**
   * Whether a tab is the General tab of a page that has not replaced `content()`.
   */
  protected usesDefaultContent(tab: string): boolean {
    return tab === AdminPage.DEFAULT_TAB && !app.registry.getTabs(this.extension.id).length && !this.subclassDefines('content');
  }

  changePageTab(tab: string) {
    this.activePageTab = tab;

    // Keep the tab in the URL so reloads and shared links open it, without a route change.
    // app.route() leaves out a non-empty route prefix (the admin's `#`), which Mithril adds when it navigates.
    const path = app.route('extension', { id: this.extension.id, tab });

    window.history.replaceState(window.history.state, '', m.route.prefix === '' ? path : m.route.prefix + path);
  }

  /**
   * The settings, for pages laid out without tabs.
   */
  content(vnode: Mithril.VnodeDOM<Attrs, this>) {
    return (
      <div className="ExtensionPage-settings">
        <div className="container">{this.settingsForm(null)}</div>
      </div>
    );
  }

  settingsRegistryKey(): string {
    return this.extension.id;
  }

  settingsFormControls(): ItemList<Mithril.Children> {
    const items = super.settingsFormControls();
    const settings = app.registry.getSettings(this.extension.id) || [];

    items.add(
      'reset',
      this.resetButton(
        settings
          .filter((e): e is Exclude<typeof e, () => Mithril.Children> => typeof e !== 'function')
          .map((e) => ({ key: e.setting, label: e.label })),
        app.translator.trans(
          'core.admin.extension.reset_settings.title_extension',
          {
            extensionTitle: this.extension.extra['flarum-extension'].title,
          },
          true
        ),
        this.extension.id
      ),
      90
    );

    return items;
  }

  noSettingsMessage(): Mithril.Children {
    return <h3 className="ExtensionPage-subHeader">{app.translator.trans('core.admin.extension.no_settings')}</h3>;
  }

  /**
   * The tabs holding settings with unsaved changes.
   *
   * Changes to settings the registry doesn't know about, such as those inside a
   * custom setting, count against the tab that custom setting is on, when there is only one.
   */
  unsavedTabs(): Set<string> {
    const dirty = Object.keys(this.dirty());
    const result = new Set<string>();

    if (!dirty.length) return result;

    const tabOfKey = new Map<string, string>();
    const customTabs = new Set<string>();

    this.settingsTabs().forEach((tab) => {
      this.settingsGroups(tab.id).forEach((group) =>
        group.settings.forEach((setting) => {
          if (typeof setting === 'function') {
            customTabs.add(tab.id);
            return;
          }

          tabOfKey.set(setting.setting, tab.id);

          const nested = (setting as { textArea?: { setting?: string } }).textArea?.setting;
          if (nested) tabOfKey.set(nested, tab.id);
        })
      );
    });

    dirty.forEach((key) => {
      const tab = tabOfKey.get(key) ?? (customTabs.size === 1 ? [...customTabs][0] : undefined);

      if (tab) result.add(tab);
    });

    return result;
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
            mariadb: 'MariaDB',
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
