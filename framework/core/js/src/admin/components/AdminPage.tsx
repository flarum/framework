import type Mithril from 'mithril';

import app from '../app';
import Page, { IPageAttrs } from '../../common/components/Page';
import Button from '../../common/components/Button';
import classList from '../../common/utils/classList';
import Stream from '../../common/utils/Stream';
import saveSettings from '../utils/saveSettings';
import AdminHeader from './AdminHeader';
import FormGroup, { FieldComponentOptions } from '../../common/components/FormGroup';
import extractText from '../../common/utils/extractText';
import LoadingModal from './LoadingModal';
import Form from '../../common/components/Form';
import ItemList from '../../common/utils/ItemList';
import SettingsSection from './SettingsSection';
import groupBySection from '../utils/groupBySection';
import type { SettingConfigInternal, SettingsSectionConfig } from '../utils/AdminRegistry';

export interface AdminHeaderOptions {
  title: Mithril.Children;
  description: Mithril.Children;
  icon: string;
  /**
   * Will be used as the class for the AdminPage.
   *
   * Will also be appended with `-header` and set as the class for the `AdminHeader` component.
   */
  className: string;
}

export type SettingsComponentOptions = FieldComponentOptions & {
  setting: string;
  /** The id of the settings section to show this setting in. */
  section?: string;
  json?: boolean;
  refreshAfterSaving?: boolean;
};

/**
 * Valid attrs that can be returned by the `headerInfo` function
 */
export type AdminHeaderAttrs = AdminHeaderOptions & Partial<Omit<Mithril.Attributes, 'class'>>;

export type SettingValue = string;
export type MutableSettings = Record<string, Stream<SettingValue>>;

export type SaveSubmitEvent = SubmitEvent & { redraw: boolean };

export default abstract class AdminPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends Page<CustomAttrs> {
  /**
   * The id of the tab that holds the settings when a page registers no tabs of its own.
   */
  static DEFAULT_TAB = 'general';

  settings: MutableSettings = {};
  settingLabels: Record<string, Mithril.Children> = {};
  refreshAfterSaving: string[] = [];
  loading: boolean = false;

  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    const className = classList('AdminPage', this.headerInfo().className);

    return (
      <div className={className}>
        {this.header(vnode)}
        <div className="container">{this.content(vnode)}</div>
      </div>
    );
  }

  /**
   * Returns the content of the AdminPage.
   */
  abstract content(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;

  /**
   * Returns the submit button for this AdminPage.
   *
   * Calls `this.saveSettings` when the button is clicked.
   */
  submitButton(): Mithril.Children {
    return (
      <Button
        type="submit"
        onclick={this.saveSettings.bind(this)}
        className="Button Button--primary"
        loading={this.loading}
        disabled={!this.isChanged()}
      >
        {app.translator.trans('core.admin.settings.submit_button')}
      </Button>
    );
  }

  /**
   * Returns a button that opens a confirmation modal to delete all settings
   * tracked by this page from the database, reverting them to their PHP-side defaults.
   *
   * Calls can pass an explicit list of setting keys to reset; otherwise all keys
   * currently tracked in `this.settings` are used.
   */
  resetButton(
    settings: import('./ResetExtensionSettingsModal').ResetSettingItem[] = Object.keys(this.settings).map((key) => ({
      key,
      label: this.settingLabels[key],
    })),
    title?: string,
    extensionId?: string
  ): Mithril.Children {
    return (
      <Button
        className="Button Button--danger"
        onclick={() => app.modal.show(() => import('./ResetExtensionSettingsModal'), { settings, title, extensionId })}
      >
        {app.translator.trans('core.admin.extension.reset_settings.button')}
      </Button>
    );
  }

  /**
   * Returns the Header component for this AdminPage.
   */
  header(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    const { title, className, ...headerAttrs } = this.headerInfo();

    return (
      <AdminHeader className={className ? `${className}-header` : undefined} {...headerAttrs}>
        {title}
      </AdminHeader>
    );
  }

  /**
   * Returns the options passed to the AdminHeader component.
   */
  headerInfo(): AdminHeaderAttrs {
    return {
      className: '',
      icon: '',
      title: '',
      description: '',
    };
  }

  /**
   * `buildSettingComponent` takes a settings object and turns it into a component.
   * Depending on the type of input, you can set the type to 'bool', 'select', or
   * any standard <input> type. Any values inside the 'extra' object will be added
   * to the component as an attribute.
   *
   * Alternatively, you can pass a callback that will be executed in ExtensionPage's
   * context to include custom JSX elements.
   *
   * @example
   *
   * {
   *    setting: 'acme.checkbox',
   *    label: app.translator.trans('acme.admin.setting_label'),
   *    type: 'bool',
   *    help: app.translator.trans('acme.admin.setting_help'),
   *    className: 'Setting-item'
   * }
   *
   * @example
   *
   * {
   *    setting: 'acme.select',
   *    label: app.translator.trans('acme.admin.setting_label'),
   *    type: 'select',
   *    options: {
   *      'option1': 'Option 1 label',
   *      'option2': 'Option 2 label',
   *    },
   *    default: 'option1',
   * }
   *
   * @example
   *
   * () => {
   *   return <p>My cool component</p>;
   * }
   */
  buildSettingComponent(entry: ((this: this) => Mithril.Children) | SettingsComponentOptions): Mithril.Children {
    if (typeof entry === 'function') {
      return entry.call(this);
    }

    const { setting, section, json, refreshAfterSaving, ...attrs } = entry;

    const originalBidi: (value?: string) => any = this.setting(setting, (entry as any).default ?? '');
    let bidi: (value?: string) => any;

    if (json) {
      bidi = function (value?: string) {
        if (arguments.length) {
          originalBidi(JSON.stringify(value));
        }

        const v = originalBidi();

        if (v) {
          return JSON.parse(v);
        }

        return v;
      };
    } else {
      bidi = originalBidi;
    }

    if (refreshAfterSaving) {
      this.refreshAfterSaving.push(setting);
    }

    return <FormGroup stream={bidi} getSetting={this.setting.bind(this)} {...attrs} />;
  }

  /**
   * The AdminRegistry key whose registered settings this page shows: an
   * extension id, or a core page's key such as `core-basics`. `null` when the
   * page builds its settings itself.
   */
  settingsRegistryKey(): string | null {
    return null;
  }

  /**
   * The registered settings, grouped into their sections, followed by the save controls.
   *
   * @param tab Only show the settings on this tab; `null` shows them all.
   */
  settingsForm(tab: string | null = null): Mithril.Children {
    const key = this.settingsRegistryKey();
    const groups = this.settingsGroups(tab);

    if (!key || !groups.length) return this.noSettingsMessage();

    const sectioned = app.registry.getSections(key).length > 0;

    return (
      <Form className={classList('AdminPage-settingsForm', sectioned && 'AdminPage-settingsForm--sectioned')}>
        {sectioned ? this.settingsSections(tab).toArray() : groups.flatMap((group) => group.settings).map(this.buildSettingComponent.bind(this))}
        <div className="Form-group Form-controls">{this.settingsFormControls().toArray()}</div>
      </Form>
    );
  }

  /**
   * The buttons under the settings form.
   */
  settingsFormControls(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('submit', this.submitButton(), 100);

    return items;
  }

  /**
   * Shown by `settingsForm()` when there are no settings to show.
   */
  noSettingsMessage(): Mithril.Children {
    return null;
  }

  /**
   * The registered settings on a tab, grouped into their sections, as one
   * `SettingsSection` each. Settings before the first section come first, without a heading.
   */
  settingsSections(tab: string | null = null): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const groups = this.settingsGroups(tab);

    groups.forEach(({ section, settings }, index) => {
      if (!section) {
        items.add('ungrouped', <div className="AdminPage-ungrouped">{settings.map(this.buildSettingComponent.bind(this))}</div>, 1000);

        return;
      }

      items.add(
        section.id,
        <SettingsSection
          id={`${this.settingsRegistryKey()}-section-${section.id}`}
          label={section.label}
          description={section.description}
          icon={section.icon}
        >
          {settings.map(this.buildSettingComponent.bind(this))}
        </SettingsSection>,
        groups.length - index
      );
    });

    return items;
  }

  /**
   * The registered settings on a tab, in display order, split into their sections.
   *
   * @param tab `null` for every tab.
   */
  settingsGroups(tab: string | null = null): { section: SettingsSectionConfig | null; settings: SettingConfigInternal[] }[] {
    const key = this.settingsRegistryKey();

    if (!key) return [];

    const sections = app.registry.getSections(key);
    const tabs = app.registry.getTabs(key);
    const firstTab = tabs[0]?.id ?? AdminPage.DEFAULT_TAB;
    const tabOf = (section: SettingsSectionConfig | null) => (section?.tab && tabs.some((t) => t.id === section.tab) ? section.tab : firstTab);

    return app.registry
      .getSettingGroups(key)
      .map((group) => ({ section: sections.find((s) => s.id === group.section) ?? null, settings: group.entries }))
      .filter((group) => tab === null || tabOf(group.section) === tab);
  }

  /**
   * Renders an ItemList as titled sections, keeping the list's keys and priorities.
   *
   * Each section names the item keys it holds. An item it doesn't name, such as
   * one another extension added, joins the section of the item before it, so it
   * stays where it was shown before. Items before the first named one are shown
   * without a heading.
   *
   * @example
   * this.itemSections(this.brandingItems(), [
   *   { id: 'logos', label: app.translator.trans('acme.admin.logos'), items: ['logo', 'favicon'] },
   *   { id: 'code', label: app.translator.trans('acme.admin.code'), items: ['custom-css'] },
   * ]).toArray()
   */
  itemSections(items: ItemList<Mithril.Children>, sections: (SettingsSectionConfig & { items: string[] })[]): ItemList<Mithril.Children> {
    const result = new ItemList<Mithril.Children>();
    const entries = Object.values(items.toObject()).sort((a, b) => b.priority - a.priority);
    const groups = groupBySection(
      entries,
      (entry) => sections.find((section) => section.items.includes(entry.itemName))?.id,
      sections.map((section) => section.id)
    );

    groups.forEach(({ section: id, entries }, index) => {
      const content = entries.map((entry) => entry.content as Mithril.Children);
      const section = sections.find((s) => s.id === id);
      const priority = groups.length - index;

      if (!section) {
        result.add('ungrouped', <div className="AdminPage-ungrouped">{content}</div>, priority);

        return;
      }

      result.add(
        section.id,
        <SettingsSection id={section.id} label={section.label} description={section.description} icon={section.icon}>
          {content}
        </SettingsSection>,
        priority
      );
    });

    return result;
  }

  /**
   * The id of the tab a registered setting is shown on.
   */
  settingsTabOf(setting: SettingConfigInternal): string {
    const key = this.settingsRegistryKey();

    return (key && app.registry.getSettingLocation(key, setting).tab?.id) || AdminPage.DEFAULT_TAB;
  }

  /**
   * Called when `saveSettings` completes successfully.
   */
  onsaved(): void {
    this.loading = false;

    app.alerts.show({ type: 'success' }, app.translator.trans('core.admin.settings.saved_message'));
  }

  /**
   * Called when `saveSettings` fails to complete.
   */
  onsavefailed(): void {
    this.loading = false;
  }

  /**
   * Returns a function that fetches the setting from the `app` global.
   *
   * An optional `label` can be provided to associate a human-readable label
   * with the setting key, which is used by `resetButton()` in the reset modal.
   */
  setting(key: string, fallback: string = '', label?: Mithril.Children): Stream<string> {
    this.settings[key] = this.settings[key] || Stream<string>(app.data.settings[key] ?? fallback);

    if (label !== undefined) {
      this.settingLabels[key] = label;
    }

    return this.settings[key];
  }

  /**
   * Returns a map of settings keys to values which includes only those which have been modified but not yet saved.
   */
  dirty(): Record<string, string> {
    const dirty: Record<string, string> = {};

    Object.keys(this.settings).forEach((key) => {
      const value = this.settings[key]();

      if (value !== app.data.settings[key]) {
        dirty[key] = value;
      }
    });

    return dirty;
  }

  /**
   * Returns the number of settings that have been modified.
   */
  isChanged(): number {
    return Object.keys(this.dirty()).length;
  }

  /**
   * Saves the modified settings to the database.
   */
  saveSettings(e: SaveSubmitEvent) {
    e.preventDefault();

    app.alerts.clear();

    this.loading = true;

    const dirty = this.dirty();

    return saveSettings(dirty)
      .then(this.onsaved.bind(this))
      .then(() => {
        if (this.refreshAfterSaving.length && Object.keys(dirty).some((setting) => this.refreshAfterSaving.includes(setting))) {
          app.modal.show(LoadingModal);
          window.location.reload();
        }
      })
      .catch(this.onsavefailed.bind(this));
  }

  static modelLocale(): Record<string, string> {
    return {
      'Flarum\\Discussion\\Discussion': extractText(app.translator.trans('core.admin.models.discussions')),
      'Flarum\\User\\User': extractText(app.translator.trans('core.admin.models.users')),
      'Flarum\\Post\\Post': extractText(app.translator.trans('core.admin.models.posts')),
    };
  }
}
