import Model from 'flarum/common/Model';
import app from 'flarum/admin/app';
import type { Extension } from 'flarum/admin/AdminApplication';
import { isProductionReady } from '../utils/versions';

export default class ExternalExtension extends Model {
  extensionId = Model.attribute<string>('extensionId');
  name = Model.attribute<string>('name');
  title = Model.attribute<string>('title');
  description = Model.attribute<string>('description');
  iconUrl = Model.attribute<string>('iconUrl');
  icon = Model.attribute<{
    name: string;
    [key: string]: string;
  }>('icon');
  highestVersion = Model.attribute<string>('highestVersion');
  httpUri = Model.attribute<string>('httpUri');
  discussUri = Model.attribute<string>('discussUri');
  vendor = Model.attribute<string>('vendor');
  isPremium = Model.attribute<boolean>('isPremium');
  isLocale = Model.attribute<boolean>('isLocale');
  locale = Model.attribute<string>('locale');
  latestFlarumVersionSupported = Model.attribute<string>('latestFlarumVersionSupported');
  downloads = Model.attribute<number>('downloads');
  readonly installed = false;

  public isSupported(): boolean {
    const currentVersion = app.data.settings.version;
    const latestCompatibleVersion = this.latestFlarumVersionSupported();

    // If stability is not the same, it's not compatible.
    if (currentVersion.split('-')[1] !== latestCompatibleVersion.split('-')[1]) {
      return false;
    }

    // Minor versions are compatible.
    return currentVersion.split('.')[0] === latestCompatibleVersion.split('.')[0];
  }

  public isProductionReady(): boolean {
    return isProductionReady(this.highestVersion());
  }

  public toLocalExtension(): Extension {
    return {
      id: this.extensionId(),
      name: this.name(),
      version: this.highestVersion(),
      description: this.description(),
      icon: this.icon() || {
        name: 'fas fa-box-open',
        backgroundColor: '#117187',
        color: '#fff',
      },
      links: {
        discuss: this.discussUri(),
        website: this.httpUri(),
      },
      extra: {
        'flarum-extension': {
          title: this.title(),
        },
      },
    };
  }
}
