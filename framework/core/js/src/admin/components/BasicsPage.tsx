import app from '../../admin/app';
import FieldSet from '../../common/components/FieldSet';
import ItemList from '../../common/utils/ItemList';
import AdminPage from './AdminPage';
import type { IPageAttrs } from '../../common/components/Page';
import type Mithril from 'mithril';
import Form from '../../common/components/Form';
import extractText from '../../common/utils/extractText';
import Button from '../../common/components/Button';
import Link from '../../common/components/Link';

export type HomePageItem = { path: string; label: Mithril.Children };
export type DriverLocale = {
  display_name: Record<string, string>;
  avatar: Record<string, string>;
  slug: Record<string, Record<string, string>>;
};

export default class BasicsPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends AdminPage<CustomAttrs> {
  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);
  }

  headerInfo() {
    return {
      className: 'BasicsPage',
      icon: 'fas fa-pencil-alt',
      title: app.translator.trans('core.admin.basics.title'),
      description: app.translator.trans('core.admin.basics.description'),
    };
  }

  content() {
    const settings = app.registry.getSettings('core-basics');

    return [
      <Form>
        {settings?.map(this.buildSettingComponent.bind(this))}
        <div className="Form-group Form-controls">{this.submitButton()}</div>
      </Form>,
    ];
  }

  /**
   * Build a list of options for the default homepage. Each option must be an
   * object with `path` and `label` properties.
   */
  static homePageItems() {
    const items = new ItemList<HomePageItem>();

    items.add('allDiscussions', {
      path: '/all',
      label: app.translator.trans('core.admin.basics.all_discussions_label'),
    });

    return items;
  }

  static driverLocale(): DriverLocale {
    return {
      display_name: {
        username: extractText(app.translator.trans('core.admin.basics.display_name_driver_options.username')),
      },
      avatar: {
        default: extractText(app.translator.trans('core.admin.basics.avatar_driver_options.default')),
      },
      slug: {
        'Flarum\\Discussion\\Discussion': {
          default: extractText(app.translator.trans('core.admin.basics.slug_driver_options.discussions.default')),
          utf8: extractText(app.translator.trans('core.admin.basics.slug_driver_options.discussions.utf8')),
        },
        'Flarum\\User\\User': {
          default: extractText(app.translator.trans('core.admin.basics.slug_driver_options.users.default')),
          id: extractText(app.translator.trans('core.admin.basics.slug_driver_options.users.id')),
          id_with_display_name: extractText(app.translator.trans('core.admin.basics.slug_driver_options.users.id_with_display_name')),
        },
      },
    };
  }

  static register() {
    app.generalIndex.group('core-basics', {
      label: app.translator.trans('core.admin.basics.title', {}, true),
      icon: {
        name: 'fas fa-pencil-alt',
      },
      link: app.route('basics'),
    });

    const localeOptions: Record<string, string> = {};
    const displayNameOptions: Record<string, string> = {};
    const avatarDriverOptions: Record<string, string> = {};
    const slugDriverOptions: Record<string, Record<string, string>> = {};

    const driverLocale = BasicsPage.driverLocale();

    Object.keys(app.data.locales).forEach((i) => {
      localeOptions[i] = `${app.data.locales[i]} (${i})`;
    });

    app.data.displayNameDrivers.forEach((identifier) => {
      displayNameOptions[identifier] = driverLocale.display_name[identifier] || identifier;
    });

    app.data.avatarDrivers.forEach((identifier) => {
      avatarDriverOptions[identifier] = driverLocale.avatar[identifier] || identifier;
    });

    Object.keys(app.data.slugDrivers).forEach((model) => {
      slugDriverOptions[model] = {};

      app.data.slugDrivers[model].forEach((option) => {
        slugDriverOptions[model][option] = (driverLocale.slug[model] && driverLocale.slug[model][option]) || option;
      });
    });

    let abandonedSyncing = false;
    let abandonedSyncMessage: Mithril.Children = null;

    function syncAbandoned() {
      if (abandonedSyncing) return;

      abandonedSyncing = true;
      abandonedSyncMessage = null;

      app
        .request<{ count: number }>({
          method: 'POST',
          url: app.forum.attribute('apiUrl') + '/extensions/abandoned/sync',
        })
        .then((response) => {
          abandonedSyncing = false;
          abandonedSyncMessage = app.translator.trans('core.admin.basics.abandoned_extensions_sync_success', { count: response.count });
          m.redraw();
        })
        .catch(() => {
          abandonedSyncing = false;
          abandonedSyncMessage = app.translator.trans('core.admin.basics.abandoned_extensions_sync_error');
          m.redraw();
        });
    }

    app.registry.for('core-basics');

    app.registry
      .registerSetting({
        type: 'text',
        setting: 'forum_title',
        label: app.translator.trans('core.admin.basics.forum_title_heading'),
      })
      .registerSetting({
        type: 'text',
        setting: 'forum_description',
        label: app.translator.trans('core.admin.basics.forum_description_heading'),
        help: app.translator.trans('core.admin.basics.forum_description_text'),
      });

    if (Object.keys(localeOptions).length > 1) {
      app.registry
        .registerSetting({
          type: 'select',
          setting: 'default_locale',
          options: localeOptions,
          label: app.translator.trans('core.admin.basics.default_language_heading'),
        })
        .registerSetting({
          type: 'switch',
          setting: 'show_language_selector',
          label: app.translator.trans('core.admin.basics.show_language_selector_label'),
        });
    }

    app.registry
      .registerSetting({
        type: 'radio',
        setting: 'default_route',
        name: 'homePage',
        options: BasicsPage.homePageItems()
          .toArray()
          .map((item: HomePageItem) => ({
            ...item,
            value: item.path,
          })),
        label: app.translator.trans('core.admin.basics.home_page_heading', {}, true),
        help: app.translator.trans('core.admin.basics.home_page_text', {}, true),
        containerClassName: 'BasicsPage-homePage',
      })
      .registerSetting({
        type: 'stacked-text',
        setting: 'welcome_title',
        textArea: {
          setting: 'welcome_message',
          cols: 80,
          rows: 6,
        },
        label: app.translator.trans('core.admin.basics.welcome_banner_heading'),
        help: app.translator.trans('core.admin.basics.welcome_banner_text'),
        containerClassName: 'BasicsPage-welcomeBanner-input',
      });

    if (Object.keys(displayNameOptions).length > 1) {
      app.registry.registerSetting({
        type: 'select',
        setting: 'display_name_driver',
        options: displayNameOptions,
        label: app.translator.trans('core.admin.basics.display_name_heading'),
        help: app.translator.trans('core.admin.basics.display_name_text'),
      });
    }

    if (Object.keys(avatarDriverOptions).length > 1) {
      app.registry.registerSetting({
        type: 'select',
        setting: 'avatar_driver',
        default: 'default',
        options: avatarDriverOptions,
        label: app.translator.trans('core.admin.basics.avatar_driver_heading'),
        help: app.translator.trans('core.admin.basics.avatar_driver_text'),
      });
    }

    Object.keys(slugDriverOptions).forEach((model) => {
      const options = slugDriverOptions[model];
      const modelLocale = AdminPage.modelLocale()[model] || model;

      if (Object.keys(options).length > 1) {
        app.registry.registerSetting({
          type: 'select',
          setting: `slug_driver_${model}`,
          options,
          label: app.translator.trans('core.admin.basics.slug_driver_heading', { model: modelLocale }),
          help: app.translator.trans('core.admin.basics.slug_driver_text', { model: modelLocale }),
        });
      }
    });

    app.registry.registerSetting(
      function (this: AdminPage) {
        return (
          <div className="Form-group">
            <label>{app.translator.trans('core.admin.basics.abandoned_extensions_heading')}</label>
            <div className="helpText">
              {app.translator.trans('core.admin.basics.abandoned_extensions_text', {
                a: <Link href="https://github.com/flarum/abandoned-extensions" target="_blank" external={true} />,
              })}
            </div>
            <div className="Form-row">
              <Button className="Button" onclick={syncAbandoned} loading={abandonedSyncing} disabled={abandonedSyncing}>
                {app.translator.trans('core.admin.basics.abandoned_extensions_sync_button')}
              </Button>
              {abandonedSyncMessage && <span className="helpText BasicsPage-abandonedSyncMessage">{abandonedSyncMessage}</span>}
            </div>
            <br />
            {this.buildSettingComponent({
              type: 'switch',
              setting: 'flarum-core.notify_admins_on_abandoned',
              label: app.translator.trans('core.admin.basics.abandoned_extensions_notify_admins_label'),
            })}
          </div>
        );
      },
      -10,
      'abandoned-extensions'
    );
  }
}
