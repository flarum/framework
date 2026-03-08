import Model from 'flarum/common/Model';
import type { Extension } from 'flarum/admin/AdminApplication';

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
  vendor = Model.attribute<string>('vendor');
  isLocale = Model.attribute<boolean>('isLocale');
  downloads = Model.attribute<number>('downloads');
  abandoned = Model.attribute<boolean>('abandoned');
  readonly installed = false;

  public toLocalExtension(): Extension {
    return {
      id: this.extensionId(),
      name: this.name(),
      version: this.highestVersion() ?? '?',
      description: this.description(),
      icon: this.icon() || {
        name: 'fas fa-box-open',
        backgroundColor: '#117187',
        color: '#fff',
      },
      links: {
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
