import SettingsModal from 'flarum/components/SettingsModal';

export default class AkismetSettingsModal extends SettingsModal {
  className() {
    return 'AkismetSettingsModal Modal--small';
  }

  title() {
    return 'Akismet Settings';
  }

  form() {
    return [
      <div className="Form-group">
        <label>API Key</label>
        <input className="FormControl" bidi={this.setting('flarum-akismet.api_key')}/>
      </div>
    ];
  }
}
