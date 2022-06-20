import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Dropdown from 'flarum/common/components/Dropdown';
import Button from 'flarum/common/components/Button';
import { IPageAttrs } from 'flarum/common/components/Page';
import classList from 'flarum/common/utils/classList';

import Tag from '../../common/models/Tag';
import sortTags from '../../common/utils/sortTags';
import tagLabel from '../../common/helpers/tagLabel';
import tagsLabel from '../../common/helpers/tagsLabel';

import type Mithril from 'mithril';
import type AdminPage from 'flarum/admin/components/AdminPage';

/**
 * Defines how the `TagSelector` will save its state to the database.
 */
export interface ITagSelectorSaveFormat {
  separation: 'json' | 'csv';
  values: 'id' | 'slug';
}

/**
 * Attributes for general usage of the `TagSelector`.
 */
export interface ITagSelectorAttrs {
  label: Mithril.Children;
  help?: Mithril.Children;
  class?: string;
  className?: string;

  /**
   * Whether multiple tags can be selected in the component.
   */
  multiselect?: boolean;
  /**
   * Event handler triggered when the user finishes tag selection.
   */
  onChange?: (tags: Tag[]) => void;
  /**
   * An array of tags that are selected.
   */
  value?: Tag[];
  /**
   * Filter function which the full list of tags are passed through which
   * can be used to hide certain tags.
   */
  tagFilter?: (tag: Tag) => boolean;
}

/**
 * Attributes for usage of the `TagSelector` within `AdminPage`'s
 * `buildSettingComponent` method.
 */
export interface ITagSelectorAttrsSpecial extends ITagSelectorAttrs {
  /**
   * How the selected tags should be stored and retrieved from the database.
   *
   * `separation` refers to the format itself, with JSON forming a valid
   * JSON string representing an array, and CSV simply separating items
   * with commas.
   *
   * `values` refers to the format of the values in the array. This can
   * either be `id` or `slug`.
   *
   * **Note that IDs are stored as strings, not numbers.**
   *
   * @example
   * { separation: "json", values: "id" }
   * // '["1", "2", "3"]'
   * @example
   * { separation: "csv", values: "slug" }
   * // 'tag-1,tag-2,tag-3'
   */
  saveFormat?: ITagSelectorSaveFormat;
}

/**
 * Attributes for `TagSelector` reserved for internal use.
 */
export interface ITagSelectorAttrsSpecialInternal extends ITagSelectorAttrsSpecial {
  /**
   * @internal
   */
  __fromBuildComponent: true;
  adminPage: AdminPage<IPageAttrs>;
  settingsKey: string;
}

/**
 * A component used for selecting one or multiple tags from a dropdown
 * list.
 *
 * Can be used within an `AdminPage` via `buildSettingComponent` or
 * can be implemented on its own.
 *
 * @example
 * ```tsx
 * {this.buildSettingComponent({
 *   type: 'flarum-tags.tag-selector',
 *   label: 'Pick a tag',
 *   help: 'Tags are cool!',
 *   setting: 'abcdefg.1',
 *   saveFormat: {
 *     separation: 'json',
 *     values: 'id',
 *   },
 * })}
 *
 * {this.buildSettingComponent({
 *   type: 'flarum-tags.tag-selector',
 *   label: 'Pick multiple tags',
 *   help: 'Tags are cool!',
 *   multiselect: true,
 *   setting: 'abcdefg.2',
 *   saveFormat: {
 *     separation: 'json',
 *     values: 'id',
 *   },
 * })}
 *
 * {this.buildSettingComponent({
 *   type: 'flarum-tags.tag-selector',
 *   label: 'Pick loads of tags!',
 *   help: 'Tags are cool!',
 *   multiselect: true,
 *   setting: 'abcdefg.3',
 *   saveFormat: {
 *     separation: 'json',
 *     values: 'id',
 *   },
 * })}
 *
 * {this.buildSettingComponent({
 *   type: 'flarum-tags.tag-selector',
 *   label: 'Pick only secondary tags',
 *   help: 'Tags are cool!',
 *   multiselect: true,
 *   setting: 'abcdefg.4',
 *   tagFilter: (t) => t.position() === null,
 *   saveFormat: {
 *     separation: 'json',
 *     values: 'id',
 *   },
 * })}
 * ```
 */
export default class TagSelector<InternalUsage extends boolean = false> extends Component<
  InternalUsage extends true ? ITagSelectorAttrsSpecialInternal : ITagSelectorAttrs
> {
  availableTags: Tag[] | null = null;
  selectedIds: string[] = [];
  loadingState: 'loaded' | 'error' | 'loading' = 'loading';
  saveFormat!: ITagSelectorSaveFormat;
  /**
   * When the number of selected tags exceeds this number, the label
   * switches to text-only.
   */
  textLabelCutoff = 3;

  oninit(vnode: Mithril.Vnode<InternalUsage extends true ? ITagSelectorAttrsSpecialInternal : ITagSelectorAttrs, this>) {
    super.oninit(vnode);

    // ensure formatting options are set
    if ('__fromBuildComponent' in this.attrs) {
      this.saveFormat = this.attrs.saveFormat || {
        separation: 'csv',
        values: 'id',
      };

      this.saveFormat.separation ||= 'csv';
      this.saveFormat.values ||= 'id';
    }

    if (this.attrs.value && !('__fromBuildComponent' in this.attrs)) {
      this.selectedIds = this.attrs.value.map((tag) => tag.id()!);
    }

    this.loadTags();
  }

  view(vnode: Mithril.Vnode<InternalUsage extends true ? ITagSelectorAttrsSpecialInternal : ITagSelectorAttrs, this>): Mithril.Children {
    if (this.loadingState !== 'loaded') {
      return (
        <div class={classList('TagSelector Form-group', this.attrs.class, this.attrs.className)}>
          {this.attrs.label && <label>{this.attrs.label}</label>}
          {this.attrs.help && <p class="helpText">{this.attrs.help}</p>}

          <Button class="Button Dropdown-toggle" disabled>
            {app.translator.trans(`flarum-tags.admin.tag_selector.${this.loadingState}_label`)}
          </Button>
        </div>
      );
    }

    return (
      <div class={classList('TagSelector Form-group', this.attrs.class, this.attrs.className)}>
        {this.attrs.label && <label>{this.attrs.label}</label>}
        {this.attrs.help && <p class="helpText">{this.attrs.help}</p>}

        <Dropdown
          className="TagSelector-Dropdown"
          buttonClassName="Button"
          accessibleToggleLabel={app.translator.trans('flarum-tags.admin.tag_selector.open_dropdown_a11y_label')}
          label={this.dropdownLabel()}
          onhide={this.onhide.bind(this)}
        >
          {this.availableTags?.map((tag) => {
            return (
              <Button
                key={tag.id()}
                icon={this.selectedIds.includes(tag.id()!) ? 'fas fa-check' : 'none'}
                class="Button"
                onclick={this.handleTagSelectToggle(tag.id()!)}
              >
                {this.tagLabel(tag)}
              </Button>
            );
          })}
        </Dropdown>
      </div>
    );
  }

  get settingValue(): string {
    if (!('__fromBuildComponent' in this.attrs)) return '';

    return this.attrs.adminPage.setting(this.attrs.settingsKey)();
  }

  /**
   * Fetches all tags from the API and loads them into the store, as well as
   * setting the default state of the selector if using the selector within
   * the settings component builder.
   */
  async loadTags() {
    this.loadingState = 'loading';
    m.redraw();

    let tags: Tag[] = await app.store.find<Tag[]>('tags');

    if ('__fromBuildComponent' in this.attrs) {
      // preload value from settings
      const settingData = this.settingValue;
      let decodedSetting: null | string[] = null;

      switch (this.saveFormat.separation) {
        default:
        case 'csv':
          // splitting an empty string results in an array element
          // with an empty string, so we need to filter that out
          decodedSetting = settingData ? settingData.split(',') : [];
          break;

        case 'json':
          try {
            decodedSetting = JSON.parse(settingData || '[]');
          } catch {
            app.alerts.show({ type: 'error' }, app.translator.trans('flarum-tags.admin.tag_selector.failed_parse'));
            decodedSetting = [];
          }
          break;
      }

      switch (this.saveFormat.values) {
        default:
        case 'id':
          this.selectedIds = decodedSetting!;
          break;

        case 'slug':
          this.selectedIds = decodedSetting!.map(
            // Use -1 as fallback for a deleted tag
            (slug) => app.store.getBy<Tag>('tags', 'slug', slug)?.id() || '-1'
          );
          break;
      }
    }

    if (typeof this.attrs.tagFilter === 'function') {
      tags = tags.filter(this.attrs.tagFilter);
    }

    this.availableTags = sortTags(tags);
    this.loadingState = 'loaded';
    m.redraw();
  }

  onhide() {
    if ('__fromBuildComponent' in this.attrs) {
      // this is being used on the admin page, so we need to save the setting
      const convertedArr = this.selectedIds
        .map((id) => {
          switch (this.saveFormat.values) {
            default:
            case 'id':
              return id;

            case 'slug':
              return app.store.getById<Tag>('tags', id)?.slug();
          }
        })
        .filter((t) => !!t) as string[];

      let stringVal = '';

      switch (this.saveFormat.separation) {
        default:
        case 'csv':
          stringVal = convertedArr.join(',');
          break;

        case 'json':
          stringVal = JSON.stringify(convertedArr);
          break;
      }

      this.attrs.adminPage.setting(this.attrs.settingsKey)(stringVal);
    }
  }

  handleTagSelectToggle(tagId: string) {
    return (e: MouseEvent) => {
      // Keep dropdown open in multiselect mode
      if (this.attrs.multiselect) {
        e.stopPropagation();

        const index = this.selectedIds.indexOf(tagId);

        if (index !== -1) {
          // Remove from list
          this.selectedIds.splice(index, 1);
        } else {
          // Add to list
          this.selectedIds.push(tagId);
        }
      } else {
        // Single select -- just replace the array
        this.selectedIds = [tagId];
      }
    };
  }

  tagLabel(tag: Tag | undefined): Mithril.Children {
    if (tag?.parent()) {
      // Show parental hierarchy
      return tagsLabel([tag.parent(), tag]);
    } else {
      return tagLabel(tag);
    }
  }

  dropdownLabel(): Mithril.Children {
    if (this.selectedIds.length > this.textLabelCutoff) {
      // X selected
      return app.translator.trans(`flarum-tags.admin.tag_selector.tags_selected_label`, { count: this.selectedIds.length });
    } else if (this.selectedIds.length > 0) {
      // Show tag label itself
      return this.selectedIds.map((id) => this.tagLabel(app.store.getById('tags', id)));
    }

    // Choose tags...
    return app.translator.trans(`flarum-tags.admin.tag_selector.choose_tags_label`);
  }
}
