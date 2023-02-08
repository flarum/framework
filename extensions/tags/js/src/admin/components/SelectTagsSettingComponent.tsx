import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import TagSelectionModal from '../../common/components/TagSelectionModal';
import tagsLabel from '../../common/helpers/tagsLabel';

import type { CommonSettingsItemOptions } from 'flarum/admin/components/AdminPage';
import type Stream from 'flarum/common/utils/Stream';
import type { ITagSelectionModalAttrs } from '../../common/components/TagSelectionModal';
import type Tag from '../../common/models/Tag';

export interface SelectTagsSettingComponentOptions extends CommonSettingsItemOptions {
  type: 'flarum-tags.select-tags';
  options?: ITagSelectionModalAttrs;
}

export interface SelectTagsSettingComponentAttrs extends SelectTagsSettingComponentOptions {
  settingValue: Stream<string>;
}

export default class SelectTagsSettingComponent<
  CustomAttrs extends SelectTagsSettingComponentAttrs = SelectTagsSettingComponentAttrs
> extends Component<CustomAttrs> {
  protected tags: Tag[] = [];
  protected loaded = false;

  view() {
    const value = JSON.parse(this.attrs.settingValue() || '[]');

    if (!this.loaded) {
      app.tagList.load(['parent']).then((tags) => {
        this.tags = tags.filter((tag) => value.includes(tag.id()));
        this.loaded = true;
        m.redraw();
      });
    }

    return (
      <div className="Form-group SelectTagsSettingComponent">
        <label>{this.attrs.label}</label>
        {this.attrs.help && <p className="helpText">{this.attrs.help}</p>}
        {!this.loaded ? (
          <LoadingIndicator size="small" display="inline" />
        ) : (
          <Button
            className="Button Button--text"
            onclick={() =>
              app.modal.show(TagSelectionModal, {
                selectedTags: this.tags,
                onsubmit: (tags: Tag[]) => {
                  this.tags = tags;
                  this.attrs.settingValue(JSON.stringify(tags.map((tag) => tag.id())));
                },
                ...this.attrs.options,
              })
            }
          >
            {!!this.tags.length ? (
              tagsLabel(this.tags)
            ) : (
              <span className="TagLabel untagged">{app.translator.trans('flarum-tags.admin.settings.button_text')}</span>
            )}
          </Button>
        )}
      </div>
    );
  }
}
