import { extend } from 'flarum/common/extend';
import SelectTagsSettingComponent from './components/SelectTagsSettingComponent';
import FormGroup from 'flarum/common/components/FormGroup';
import type { IFormGroupAttrs } from 'flarum/common/components/FormGroup';

export default function () {
  extend(FormGroup.prototype, 'customFieldComponents', function (items) {
    items.add('flarum-tags.select-tags', (attrs: IFormGroupAttrs) => {
      return <SelectTagsSettingComponent {...attrs} settingValue={attrs.bidi} />;
    });
  });
}
