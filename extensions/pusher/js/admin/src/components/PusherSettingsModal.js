import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import saveConfig from 'flarum/utils/saveConfig';

export default class PusherSettingsModal extends Modal {
  constructor(...args) {
    super(...args);

    this.appId = m.prop(app.config['pusher.app_id'] || '');
    this.appKey = m.prop(app.config['pusher.app_key'] || '');
    this.appSecret = m.prop(app.config['pusher.app_secret'] || '');
  }

  className() {
    return 'PusherSettingsModal Modal--small';
  }

  title() {
    return 'Pusher Settings';
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>App ID</label>
            <input className="FormControl" value={this.appId()} oninput={m.withAttr('value', this.appId)}/>
          </div>

          <div className="Form-group">
            <label>App Key</label>
            <input className="FormControl" value={this.appKey()} oninput={m.withAttr('value', this.appKey)}/>
          </div>

          <div className="Form-group">
            <label>App Secret</label>
            <input className="FormControl" value={this.appSecret()} oninput={m.withAttr('value', this.appSecret)}/>
          </div>

          <div className="Form-group">
            {Button.component({
              type: 'submit',
              className: 'Button Button--primary PusherSettingsModal-save',
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
      'pusher.app_id': this.appId(),
      'pusher.app_key': this.appKey(),
      'pusher.app_secret': this.appSecret()
    }).then(
      () => this.hide(),
      () => {
        this.loading = false;
        m.redraw();
      }
    );
  }
}
