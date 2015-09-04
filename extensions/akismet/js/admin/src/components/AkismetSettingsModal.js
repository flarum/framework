import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import saveConfig from 'flarum/utils/saveConfig';

export default class AkismetSettingsModal extends Modal {
  constructor(...args) {
    super(...args);

    this.apiKey = m.prop(app.config['akismet.api_key'] || '');
  }

  className() {
    return 'AkismetSettingsModal Modal--small';
  }

  title() {
    return 'Akismet Settings';
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>API Key</label>
            <input className="FormControl" value={this.apiKey()} oninput={m.withAttr('value', this.apiKey)}/>
          </div>

          <div className="Form-group">
            {Button.component({
              type: 'submit',
              className: 'Button Button--primary AkismetSettingsModal-save',
              loading: this.loading,
              children: 'Save Changes'
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
      'akismet.api_key': this.apiKey()
    }).then(
      () => this.hide(),
      () => {
        this.loading = false;
        m.redraw();
      }
    );
  }
}
