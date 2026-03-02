import app from 'flarum/admin/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Form from 'flarum/common/components/Form';
import Button from 'flarum/common/components/Button';
import type Mithril from 'mithril';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import ItemList from 'flarum/common/utils/ItemList';
import Input from 'flarum/common/components/Input';
import Stream from 'flarum/common/utils/Stream';
import Alert from 'flarum/common/components/Alert';
import listItems from 'flarum/common/helpers/listItems';
import Pagination from 'flarum/common/components/Pagination';
import InfoTile from 'flarum/common/components/InfoTile';
import classList from 'flarum/common/utils/classList';
import { throttle } from 'flarum/common/utils/throttleDebounce';

import type ExternalExtension from '../models/ExternalExtension';
import ExtensionCard from './ExtensionCard';

export interface IDiscoverSectionAttrs extends ComponentAttrs {}

export default class DiscoverSection<CustomAttrs extends IDiscoverSectionAttrs = IDiscoverSectionAttrs> extends Component<CustomAttrs> {
  protected search = Stream('');
  protected warningsDismissed = Stream(false);

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    app.extensionManager.extensions.goto(1);

    this.warningsDismissed(localStorage.getItem('flarum-extension-manager.warningsDismissed') === 'true');
  }

  load(page = 1) {
    app.extensionManager.extensions.goto(page);
  }

  view() {
    return (
      <div className="ExtensionPage-settings ExtensionManager-DiscoverSection">
        <div className="container">
          <Form>
            <div className="Form-group">
              <label>{app.translator.trans('flarum-extension-manager.admin.sections.discover.title')}</label>
              <div className="helpText">
                {app.translator.trans('flarum-extension-manager.admin.sections.discover.description')}
                {this.warningsDismissed() && (
                  <Button
                    className="Button Button--text Button--warning Button--more"
                    icon="fas fa-exclamation-triangle"
                    onclick={() => this.setWarningDismissed(false)}
                  />
                )}
              </div>
            </div>
            {!this.warningsDismissed() && (
              <div className="ExtensionManager-warnings Form-group">
                <Alert className="ExtensionManager-primaryWarning" type="warning" dismissible={true} ondismiss={() => this.setWarningDismissed(true)}>
                  <ul>{listItems(this.warningItems().toArray())}</ul>
                </Alert>
              </div>
            )}
            <div className="Tabs">
              <div className="Tabs-nav">{this.tabItems().toArray()}</div>
              <div className="Tabs-content">
                <hr className="Tabs-divider" />
                <div className="ExtensionManager-DiscoverSection-toolbar">
                  <div className="ExtensionManager-DiscoverSection-toolbar-primary">{this.toolbarPrimaryItems().toArray()}</div>
                </div>
                {this.extensionList()}
                <div className="ExtensionManager-DiscoverSection-footer">{this.footerItems().toArray()}</div>
              </div>
            </div>
          </Form>
        </div>
      </div>
    );
  }

  /**
   * Maps tab keys to the type filter value forwarded to the Packagist search API.
   * The empty-string key ("") means no type filter (show all flarum-extension packages).
   */
  tabFilters(): Record<string, { label: Mithril.Children; packagistType: string | null }> {
    return {
      '': {
        label: app.translator.trans('flarum-extension-manager.admin.sections.discover.tabs.discover'),
        packagistType: null,
      },
      extension: {
        label: app.translator.trans('flarum-extension-manager.admin.sections.discover.tabs.extensions'),
        packagistType: 'extension',
      },
      locale: {
        label: app.translator.trans('flarum-extension-manager.admin.sections.discover.tabs.languages'),
        packagistType: 'locale',
      },
      theme: {
        label: app.translator.trans('flarum-extension-manager.admin.sections.discover.tabs.themes'),
        packagistType: 'theme',
      },
    };
  }

  tabItems() {
    const items = new ItemList();

    const tabs = this.tabFilters();

    Object.keys(tabs).forEach((key) => {
      const tab = tabs[key];
      const currentType = app.extensionManager.extensions.getParams().filter?.type ?? null;
      const isActive = tab.packagistType === null ? !currentType : currentType === tab.packagistType;

      items.add(
        key,
        <Button
          className="Button Button--link"
          active={isActive}
          onclick={() => {
            app.extensionManager.extensions.changeFilter('type', tab.packagistType ?? undefined);
          }}
        >
          {tab.label}
        </Button>
      );
    });

    return items;
  }

  warningItems() {
    const items = new ItemList<Mithril.Children>();

    items.add('accessWarning', app.translator.trans('flarum-extension-manager.admin.settings.access_warning'));

    if (app.data.debugEnabled) {
      items.add('devModeWarning', app.translator.trans('flarum-extension-manager.admin.settings.debug_mode_warning'));
    }

    return items;
  }

  private applySearch = throttle(1200, (value: string) => {
    const params = app.extensionManager.extensions.getParams();

    app.extensionManager.extensions.refreshParams({ ...params, filter: { ...params.filter, q: value } }, 1);
  });

  toolbarPrimaryItems() {
    const items = new ItemList();

    items.add(
      'search',
      <Input
        value={this.search()}
        onchange={(value: string) => {
          this.search(value);
          this.applySearch(value);
        }}
        inputAttrs={{ className: 'FormControl-alt' }}
        clearable={true}
        placeholder={app.translator.trans('flarum-extension-manager.admin.sections.discover.search')}
        prefixIcon="fas fa-search"
      />
    );

    return items;
  }

  extensionList() {
    if (!app.extensionManager.extensions.hasItems() && app.extensionManager.extensions.isLoading()) {
      return <LoadingIndicator display="block" />;
    }

    if (!app.extensionManager.extensions.hasItems()) {
      return (
        <div className="ExtensionManager-DiscoverSection-list ExtensionManager-DiscoverSection-list--empty">
          <InfoTile icon="fas fa-plug-circle-exclamation">
            {app.translator.trans('flarum-extension-manager.admin.sections.discover.empty_results')}
          </InfoTile>
        </div>
      );
    }

    return (
      <div
        className={classList('ExtensionManager-DiscoverSection-list', {
          'loading-container': app.extensionManager.extensions.isLoading(),
        })}
      >
        <div className="ExtensionManager-DiscoverSection-list-inner">
          {app.extensionManager.extensions
            .getPages()
            .map((page) => page.items.map((extension: ExternalExtension) => <ExtensionCard extension={extension} key={extension.name()} />))}
        </div>
        {app.extensionManager.extensions.hasItems() && app.extensionManager.extensions.isLoading() && <LoadingIndicator size="large" />}
      </div>
    );
  }

  footerItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'pagination',
      <Pagination
        total={app.extensionManager.extensions.totalItems}
        perPage={app.extensionManager.extensions.pageSize}
        currentPage={app.extensionManager.extensions.getLocation().page}
        onChange={(page: number) => {
          const current = app.extensionManager.extensions.getLocation().page;

          if (current === page) {
            return;
          }

          this.load(page);
        }}
      />
    );

    return items;
  }

  private setWarningDismissed(dismissed: boolean) {
    this.warningsDismissed(dismissed);
    localStorage.setItem('flarum-extension-manager.warningsDismissed', dismissed ? 'true' : 'false');
  }
}
