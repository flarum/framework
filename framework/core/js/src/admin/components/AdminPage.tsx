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
  settings: MutableSettings = {};
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
      <Button onclick={this.saveSettings.bind(this)} className="Button Button--primary" loading={this.loading} disabled={!this.isChanged()}>
        {app.translator.trans('core.admin.settings.submit_button')}
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

    const { setting, json, refreshAfterSaving, ...attrs } = entry;

    const originalBidi: (value?: string) => any = this.setting(setting);
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

    return <FormGroup stream={bidi} {...attrs} />;
  }

  /**
   * Called when `saveSettings` completes successfully.
   */
  onsaved(): void {
    this.loading = false;

    app.alerts.show({ type: 'success' }, app.translator.trans('core.admin.settings.saved_message'));
  }

  /**
   * Returns a function that fetches the setting from the `app` global.
   */
  setting(key: string, fallback: string = ''): Stream<string> {
    this.settings[key] = this.settings[key] || Stream<string>(app.data.settings[key] || fallback);

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
      });
  }

  modelLocale(): Record<string, string> {
    return {
      'Flarum\\Discussion\\Discussion': extractText(app.translator.trans('core.admin.models.discussions')),
      'Flarum\\User\\User': extractText(app.translator.trans('core.admin.models.users')),
      'Flarum\\Post\\Post': extractText(app.translator.trans('core.admin.models.posts')),
    };
  }
}
