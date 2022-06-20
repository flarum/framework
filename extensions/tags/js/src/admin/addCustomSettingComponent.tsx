import AdminPage, { CommonSettingsItemOptions } from 'flarum/admin/components/AdminPage';
import { extend } from 'flarum/common/extend';
import TagSelectorComponent, { ITagSelectorAttrsSpecial } from './components/TagSelector';

export default function addCustomSettingComponent() {
  extend(AdminPage.prototype, 'customSettingComponents', function (items) {
    items.add('flarum-tags.tag-selector', (attrs: ITagSelectorAttrsSpecial & CommonSettingsItemOptions) => {
      const { setting } = attrs;

      return <TagSelectorComponent<true> {...attrs} __fromBuildComponent={true} adminPage={this} settingsKey={setting} />;
    });
  });
}
