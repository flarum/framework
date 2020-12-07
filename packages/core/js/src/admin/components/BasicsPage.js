import Page from '../../common/components/Page';
import FieldSet from '../../common/components/FieldSet';
import Select from '../../common/components/Select';
import Button from '../../common/components/Button';
import saveSettings from '../utils/saveSettings';
import ItemList from '../../common/utils/ItemList';
import Switch from '../../common/components/Switch';
import Stream from '../../common/utils/Stream';
import withAttr from '../../common/utils/withAttr';
import AdminHeader from './AdminHeader';

export default class BasicsPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);

    this.loading = false;

    this.fields = [
      'forum_title',
      'forum_description',
      'default_locale',
      'show_language_selector',
      'default_route',
      'welcome_title',
      'welcome_message',
      'display_name_driver',
    ];

    this.localeOptions = {};
    const locales = app.data.locales;
    for (const i in locales) {
      this.localeOptions[i] = `${locales[i]} (${i})`;
    }

    this.displayNameOptions = {};
    const displayNameDrivers = app.data.displayNameDrivers;
    displayNameDrivers.forEach(function (identifier) {
      this.displayNameOptions[identifier] = identifier;
    }, this);

    this.slugDriverOptions = {};
    Object.keys(app.data.slugDrivers).forEach((model) => {
      this.fields.push(`slug_driver_${model}`);
      this.slugDriverOptions[model] = {};

      app.data.slugDrivers[model].forEach((option) => {
        this.slugDriverOptions[model][option] = option;
      });
    });

    this.values = {};

    const settings = app.data.settings;
    this.fields.forEach((key) => (this.values[key] = Stream(settings[key])));

    if (!this.values.display_name_driver() && displayNameDrivers.includes('username')) this.values.display_name_driver('username');

    Object.keys(app.data.slugDrivers).forEach((model) => {
      if (!this.values[`slug_driver_${model}`]() && 'default' in this.slugDriverOptions[model]) {
        this.values[`slug_driver_${model}`]('default');
      }
    });

    if (typeof this.values.show_language_selector() !== 'number') this.values.show_language_selector(1);
  }

  view() {
    return (
      <div className="BasicsPage">
        <AdminHeader icon="fas fa-pencil-alt" description={app.translator.trans('core.admin.basics.description')} className="BasicsPage-header">
          {app.translator.trans('core.admin.basics.title')}
        </AdminHeader>
        <div className="container">
          <form onsubmit={this.onsubmit.bind(this)}>
            {FieldSet.component(
              {
                label: app.translator.trans('core.admin.basics.forum_title_heading'),
              },
              [<input className="FormControl" bidi={this.values.forum_title} />]
            )}

            {FieldSet.component(
              {
                label: app.translator.trans('core.admin.basics.forum_description_heading'),
              },
              [
                <div className="helpText">{app.translator.trans('core.admin.basics.forum_description_text')}</div>,
                <textarea className="FormControl" bidi={this.values.forum_description} />,
              ]
            )}

            {Object.keys(this.localeOptions).length > 1
              ? FieldSet.component(
                  {
                    label: app.translator.trans('core.admin.basics.default_language_heading'),
                  },
                  [
                    Select.component({
                      options: this.localeOptions,
                      value: this.values.default_locale(),
                      onchange: this.values.default_locale,
                    }),
                    Switch.component(
                      {
                        state: this.values.show_language_selector(),
                        onchange: this.values.show_language_selector,
                      },
                      app.translator.trans('core.admin.basics.show_language_selector_label')
                    ),
                  ]
                )
              : ''}

            {FieldSet.component(
              {
                label: app.translator.trans('core.admin.basics.home_page_heading'),
                className: 'BasicsPage-homePage',
              },
              [
                <div className="helpText">{app.translator.trans('core.admin.basics.home_page_text')}</div>,
                this.homePageItems()
                  .toArray()
                  .map(({ path, label }) => (
                    <label className="checkbox">
                      <input
                        type="radio"
                        name="homePage"
                        value={path}
                        checked={this.values.default_route() === path}
                        onclick={withAttr('value', this.values.default_route)}
                      />
                      {label}
                    </label>
                  )),
              ]
            )}

            {FieldSet.component(
              {
                label: app.translator.trans('core.admin.basics.welcome_banner_heading'),
                className: 'BasicsPage-welcomeBanner',
              },
              [
                <div className="helpText">{app.translator.trans('core.admin.basics.welcome_banner_text')}</div>,
                <div className="BasicsPage-welcomeBanner-input">
                  <input className="FormControl" bidi={this.values.welcome_title} />
                  <textarea className="FormControl" bidi={this.values.welcome_message} />
                </div>,
              ]
            )}

            {Object.keys(this.displayNameOptions).length > 1 ? (
              <FieldSet label={app.translator.trans('core.admin.basics.display_name_heading')}>
                <div className="helpText">{app.translator.trans('core.admin.basics.display_name_text')}</div>
                <Select
                  options={this.displayNameOptions}
                  value={this.values.display_name_driver()}
                  onchange={this.values.display_name_driver}
                ></Select>
              </FieldSet>
            ) : (
              ''
            )}

            {Object.keys(this.slugDriverOptions).map((model) => {
              const options = this.slugDriverOptions[model];
              if (Object.keys(options).length > 1) {
                return (
                  <FieldSet label={app.translator.trans('core.admin.basics.slug_driver_heading', { model })}>
                    <div className="helpText">{app.translator.trans('core.admin.basics.slug_driver_text', { model })}</div>
                    <Select options={options} value={this.values[`slug_driver_${model}`]()} onchange={this.values[`slug_driver_${model}`]}></Select>
                  </FieldSet>
                );
              }
            })}

            {Button.component(
              {
                type: 'submit',
                className: 'Button Button--primary',
                loading: this.loading,
                disabled: !this.changed(),
              },
              app.translator.trans('core.admin.basics.submit_button')
            )}
          </form>
        </div>
      </div>
    );
  }

  changed() {
    return this.fields.some((key) => this.values[key]() !== app.data.settings[key]);
  }

  /**
   * Build a list of options for the default homepage. Each option must be an
   * object with `path` and `label` properties.
   *
   * @return {ItemList}
   * @public
   */
  homePageItems() {
    const items = new ItemList();

    items.add('allDiscussions', {
      path: '/all',
      label: app.translator.trans('core.admin.basics.all_discussions_label'),
    });

    return items;
  }

  onsubmit(e) {
    e.preventDefault();

    if (this.loading) return;

    this.loading = true;
    app.alerts.dismiss(this.successAlert);

    const settings = {};

    this.fields.forEach((key) => (settings[key] = this.values[key]()));

    saveSettings(settings)
      .then(() => {
        this.successAlert = app.alerts.show({ type: 'success' }, app.translator.trans('core.admin.basics.saved_message'));
      })
      .catch(() => {})
      .then(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
