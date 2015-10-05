import Component from 'flarum/Component';
import Button from 'flarum/components/Button';
import Switch from 'flarum/components/Switch';
import EditCustomCssModal from 'flarum/components/EditCustomCssModal';
import saveConfig from 'flarum/utils/saveConfig';

export default class AppearancePage extends Component {
  constructor(...args) {
    super(...args);

    this.primaryColor = m.prop(app.config.theme_primary_color);
    this.secondaryColor = m.prop(app.config.theme_secondary_color);
    this.darkMode = m.prop(app.config.theme_dark_mode === '1');
    this.coloredHeader = m.prop(app.config.theme_colored_header === '1');
  }

  view() {
    return (
      <div className="AppearancePage">
        <div className="container">
          <form onsubmit={this.onsubmit.bind(this)}>
            <fieldset className="AppearancePage-colors">
              <legend>{app.trans('core.admin.appearance_colors_heading')}</legend>
              <div className="helpText">
                {app.trans('core.admin.appearance_colors_text')}
              </div>

              <div className="AppearancePage-colors-input">
                <input className="FormControl" placeholder="#aaaaaa" value={this.primaryColor()} onchange={m.withAttr('value', this.primaryColor)}/>
                <input className="FormControl" placeholder="#aaaaaa" value={this.secondaryColor()} onchange={m.withAttr('value', this.secondaryColor)}/>
              </div>

              {Switch.component({
                state: this.darkMode(),
                children: app.trans('core.admin.appearance_dark_mode_label'),
                onchange: this.darkMode
              })}

              {Switch.component({
                state: this.coloredHeader(),
                children: app.trans('core.admin.appearance_colored_header_label'),
                onchange: this.coloredHeader
              })}

              {Button.component({
                className: 'Button Button--primary',
                type: 'submit',
                children: app.trans('core.admin.appearance_submit_button'),
                loading: this.loading
              })}
            </fieldset>
          </form>

          <fieldset>
            <legend>{app.trans('core.admin.appearance_custom_styles_heading')}</legend>
            <div className="helpText">
              {app.trans('core.admin.appearance_custom_styles_text')}
            </div>
            {Button.component({
              className: 'Button',
              children: app.trans('core.admin.appearance_edit_css_button'),
              onclick: () => app.modal.show(new EditCustomCssModal())
            })}
          </fieldset>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    const hex = /^#[0-9a-f]{3}([0-9a-f]{3})?$/i;

    if (!hex.test(this.primaryColor()) || !hex.test(this.secondaryColor())) {
      alert(app.trans('core.admin.appearance_enter_hex_message'));
      return;
    }

    this.loading = true;

    saveConfig({
      theme_primary_color: this.primaryColor(),
      theme_secondary_color: this.secondaryColor(),
      theme_dark_mode: this.darkMode(),
      theme_colored_header: this.coloredHeader()
    }).then(() => window.location.reload());
  }
}
