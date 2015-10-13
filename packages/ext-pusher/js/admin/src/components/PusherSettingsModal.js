import SettingsModal from 'flarum/components/SettingsModal';

export default class PusherSettingsModal extends SettingsModal {
  className() {
    return 'PusherSettingsModal Modal--small';
  }

  title() {
    return 'Pusher Settings';
  }

  form() {
    return [
      <div className="Form-group">
        <label>App ID</label>
        <input className="FormControl" bidi={this.setting('flarum-pusher.app_id')}/>
      </div>,

      <div className="Form-group">
        <label>App Key</label>
        <input className="FormControl" bidi={this.setting('flarum-pusher.app_key')}/>
      </div>,

      <div className="Form-group">
        <label>App Secret</label>
        <input className="FormControl" bidi={this.setting('flarum-pusher.app_secret')}/>
      </div>
    ];
  }
}
