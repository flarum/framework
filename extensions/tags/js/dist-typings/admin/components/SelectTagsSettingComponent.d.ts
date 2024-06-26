/// <reference types="mithril" />
import Component from 'flarum/common/Component';
import type { CommonFieldOptions } from 'flarum/common/components/FormGroup';
import type Stream from 'flarum/common/utils/Stream';
import type { ITagSelectionModalAttrs } from '../../common/components/TagSelectionModal';
import type Tag from '../../common/models/Tag';
export interface SelectTagsSettingComponentOptions extends CommonFieldOptions {
    type: 'flarum-tags.select-tags';
    options?: ITagSelectionModalAttrs;
}
export interface SelectTagsSettingComponentAttrs extends SelectTagsSettingComponentOptions {
    settingValue: Stream<string>;
}
export default class SelectTagsSettingComponent<CustomAttrs extends SelectTagsSettingComponentAttrs = SelectTagsSettingComponentAttrs> extends Component<CustomAttrs> {
    protected tags: Tag[];
    protected loaded: boolean;
    view(): JSX.Element;
}
