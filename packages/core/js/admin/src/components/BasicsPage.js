import Component from 'flarum/Component';
import FieldSet from 'flarum/components/FieldSet';
import Select from 'flarum/components/Select';
import Button from 'flarum/components/Button';
import Alert from 'flarum/components/Alert';
import saveConfig from 'flarum/utils/saveConfig';

export default class BasicsPage extends Component {
  constructor(...args) {
    super(...args);

    this.loading = false;

    this.fields = [
      'forum_title',
      'forum_description',
      'default_locale',
      'default_route',
      'welcome_title',
      'welcome_message'
    ];
    this.values = {};

    const config = app.config;
    this.fields.forEach(key => this.values[key] = m.prop(config[key]));

    this.localeOptions = {};
    const locales = app.locales;
    for (const i in locales) {
      this.localeOptions[i] = `${locales[i]} (${i})`;
    }
  }

  view() {
    return (
      <div className="BasicsPage">
        <div className="container">
          <form onsubmit={this.onsubmit.bind(this)}>
            {FieldSet.component({
              label: 'Forum Title',
              children: [
                <input className="FormControl" value={this.values.forum_title()} oninput={m.withAttr('value', this.values.forum_title)}/>
              ]
            })}

            {FieldSet.component({
              label: 'Forum Description',
              children: [
                <div className="helpText">
                  Enter a short sentence or two that describes your community. This will appear in the meta tag and show up in search engines.
                </div>,
                <textarea className="FormControl" value={this.values.forum_description()} oninput={m.withAttr('value', this.values.forum_description)}/>
              ]
            })}

            {Object.keys(this.localeOptions).length > 1
              ? FieldSet.component({
                label: 'Default Language',
                children: [
                  Select.component({
                    options: this.localeOptions,
                    onchange: this.values.default_locale
                  })
                ]
              })
              : ''}

            {FieldSet.component({
              label: 'Home Page',
              className: 'BasicsPage-homePage',
              children: [
                <div className="helpText">
                  Choose the page which users will first see when they visit your forum. If entering a custom value, use the path relative to the forum root.
                </div>,
                <label className="checkbox">
                  <input type="radio" name="homePage" value="/all" checked={this.values.default_route() === '/all'} onclick={m.withAttr('value', this.values.default_route)}/>
                  All Discussions
                </label>,
                <label className="checkbox">
                  <input type="radio" name="homePage" value="custom" checked={this.values.default_route() !== '/all'} onclick={() => {
                    this.values.default_route('');
                    m.redraw(true);
                    this.$('.BasicsPage-homePage input').select();
                  }}/>
                  Custom <input className="FormControl" value={this.values.default_route()} oninput={m.withAttr('value', this.values.default_route)} style={this.values.default_route() !== '/all' ? 'margin-top: 5px' : 'display:none'}/>
                </label>
              ]
            })}

            {FieldSet.component({
              label: 'Welcome Banner',
              className: 'BasicsPage-welcomeBanner',
              children: [
                <div className="helpText">
                  Configure the text that displays in the banner on the All Discussions page. Use this to welcome guests to your forum.
                </div>,
                <div className="BasicsPage-welcomeBanner-input">
                  <input className="FormControl" value={this.values.welcome_title()} oninput={m.withAttr('value', this.values.welcome_title)}/>
                  <textarea className="FormControl" value={this.values.welcome_message()} oninput={m.withAttr('value', this.values.welcome_message)}/>
                </div>
              ]
            })}

            {Button.component({
              type: 'submit',
              className: 'Button Button--primary',
              children: 'Save Changes',
              loading: this.loading,
              disabled: !this.changed()
            })}
          </form>
        </div>
      </div>
    );
  }

  changed() {
    const config = app.config;

    return this.fields.some(key => this.values[key]() !== config[key]);
  }

  onsubmit(e) {
    e.preventDefault();

    if (this.loading) return;

    this.loading = true;
    app.alerts.dismiss(this.successAlert);

    const config = {};

    this.fields.forEach(key => config[key] = this.values[key]());

    saveConfig(config)
      .then(() => {
        app.alerts.show(this.successAlert = new Alert({type: 'success', children: 'Your changes were saved.'}));
      })
      .finally(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
