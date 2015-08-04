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
              <legend>Colors</legend>
              <div className="helpText">
                Choose two colors to theme your forum with. The first will be used as a highlight color, while the second will be used to style background elements.
              </div>

              <div className="AppearancePage-colors-input">
                <input className="FormControl" placeholder="#aaaaaa" value={this.primaryColor()} onchange={m.withAttr('value', this.primaryColor)}/>
                <input className="FormControl" placeholder="#aaaaaa" value={this.secondaryColor()} onchange={m.withAttr('value', this.secondaryColor)}/>
              </div>

              {Switch.component({
                state: this.darkMode(),
                children: 'Dark Mode',
                onchange: this.darkMode
              })}

              {Switch.component({
                state: this.coloredHeader(),
                children: 'Colored Header',
                onchange: this.coloredHeader
              })}

              {Button.component({
                className: 'Button Button--primary',
                children: 'Save Changes',
                loading: this.loading
              })}
            </fieldset>
          </form>

          <fieldset>
            <legend>Custom Styles</legend>
            <div className="helpText">
              Customize your forum's appearance by adding your own LESS/CSS code to be applied on top of Flarum's default styles.
            </div>
            {Button.component({
              className: 'Button',
              children: 'Edit Custom CSS',
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
      alert('Please enter a hexadecimal color code.');
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
