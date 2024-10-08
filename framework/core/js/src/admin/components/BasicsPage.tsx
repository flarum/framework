import app from '../../admin/app';
import FieldSet from '../../common/components/FieldSet';
import ItemList from '../../common/utils/ItemList';
import AdminPage from './AdminPage';
import type { IPageAttrs } from '../../common/components/Page';
import Mithril from 'mithril';

export type HomePageItem = { path: string; label: Mithril.Children };

export default class BasicsPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends AdminPage<CustomAttrs> {
  localeOptions: Record<string, string> = {};
  displayNameOptions: Record<string, string> = {};
  slugDriverOptions: Record<string, Record<string, string>> = {};

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    Object.keys(app.data.locales).forEach((i) => {
      this.localeOptions[i] = `${app.data.locales[i]} (${i})`;
    });

    app.data.displayNameDrivers.forEach((identifier) => {
      this.displayNameOptions[identifier] = identifier;
    });

    Object.keys(app.data.slugDrivers).forEach((model) => {
      this.slugDriverOptions[model] = {};

      app.data.slugDrivers[model].forEach((option) => {
        this.slugDriverOptions[model][option] = option;
      });
    });
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
    return [
      <div className="Form">
        {this.contentItems().toArray()}
        {this.submitButton()}
      </div>,
    ];
  }

  /**
   * Build a list of options for the default homepage. Each option must be an
   * object with `path` and `label` properties.
   */
  homePageItems() {
    const items = new ItemList<HomePageItem>();

    items.add('allDiscussions', {
      path: '/all',
      label: app.translator.trans('core.admin.basics.all_discussions_label'),
    });

    return items;
  }

  contentItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'forum-title',
      this.buildSettingComponent({
        type: 'text',
        setting: 'forum_title',
        label: app.translator.trans('core.admin.basics.forum_title_heading'),
      }),
      100
    );

    items.add(
      'forum-description',
      this.buildSettingComponent({
        type: 'text',
        setting: 'forum_description',
        label: app.translator.trans('core.admin.basics.forum_description_heading'),
        help: app.translator.trans('core.admin.basics.forum_description_text'),
      }),
      90
    );

    Object.keys(this.localeOptions).length > 1 &&
      items.add(
        'default-locale',
        <>
          {this.buildSettingComponent({
            type: 'select',
            setting: 'default_locale',
            options: this.localeOptions,
            label: app.translator.trans('core.admin.basics.default_language_heading'),
          })}
          {this.buildSettingComponent({
            type: 'switch',
            setting: 'show_language_selector',
            label: app.translator.trans('core.admin.basics.show_language_selector_label'),
          })}
        </>,
        80
      );

    items.add(
      'home-page',
      <FieldSet className="BasicsPage-homePage Form-group" label={app.translator.trans('core.admin.basics.home_page_heading')}>
        <div className="helpText">{app.translator.trans('core.admin.basics.home_page_text')}</div>
        {this.homePageItems()
          .toArray()
          .map(({ path, label }) => (
            <label className="checkbox">
              <input type="radio" name="homePage" value={path} bidi={this.setting('default_route')} />
              {label}
            </label>
          ))}
      </FieldSet>,
      70
    );

    items.add(
      'welcome-banner',
      <div className="Form-group BasicsPage-welcomeBanner-input">
        <label>{app.translator.trans('core.admin.basics.welcome_banner_heading')}</label>
        <div className="helpText">{app.translator.trans('core.admin.basics.welcome_banner_text')}</div>
        <input type="text" className="FormControl" bidi={this.setting('welcome_title')} />
        <textarea className="FormControl" bidi={this.setting('welcome_message')} />
      </div>,
      60
    );

    Object.keys(this.displayNameOptions).length > 1 &&
      items.add(
        'display-name-driver',

        this.buildSettingComponent({
          type: 'select',
          setting: 'display_name_driver',
          options: this.displayNameOptions,
          label: app.translator.trans('core.admin.basics.display_name_heading'),
          help: app.translator.trans('core.admin.basics.display_name_text'),
        }),
        50
      );

    items.add(
      'slug-driver',
      Object.keys(this.slugDriverOptions).map((model) => {
        const options = this.slugDriverOptions[model];

        if (Object.keys(options).length > 1) {
          return this.buildSettingComponent({
            type: 'select',
            setting: `slug_driver_${model}`,
            options,
            label: app.translator.trans('core.admin.basics.slug_driver_heading', { model }),
            help: app.translator.trans('core.admin.basics.slug_driver_text', { model }),
          });
        }

        return null;
      }),
      40
    );

    return items;
  }
}
