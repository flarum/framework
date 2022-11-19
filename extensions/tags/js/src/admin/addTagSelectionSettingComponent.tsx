import { extend } from 'flarum/common/extend';
import AdminPage from 'flarum/admin/components/AdminPage';
import SelectTagsSettingComponent from './components/SelectTagsSettingComponent';

export default function () {
  extend(AdminPage.prototype, 'customSettingComponents', function (items) {
    items.add('flarum-tags.select-tags', (attrs) => {
      return <SelectTagsSettingComponent {...attrs} settingValue={this.settings[attrs.setting]} />;
    });
  });
}
