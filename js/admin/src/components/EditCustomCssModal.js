import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import saveConfig from 'flarum/utils/saveConfig';

export default class EditCustomCssModal extends Modal {
  constructor(...args) {
    super(...args);

    this.customLess = m.prop(app.config.custom_less || '');
  }

  className() {
    return 'EditCustomCssModal Modal--large';
  }

  title() {
    return 'Edit Custom CSS';
  }

  content() {
    return (
      <div className="Modal-body">
        <p>Customize your forum's appearance by adding your own LESS/CSS code to be applied on top of Flarum's default styles. <a href="">Read the documentation</a> for more information.</p>

        <div className="Form">
          <div className="Form-group">
            <textarea className="FormControl" rows="30" value={this.customLess()} onchange={m.withAttr('value', this.customLess)}/>
          </div>

          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary',
              children: 'Save Changes',
              loading: this.loading
            })}
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    saveConfig({
      custom_less: this.customLess()
    }).then(() => window.location.reload());
  }
}
