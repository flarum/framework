import app from 'flarum/forum/app';
import type Mithril from 'mithril';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import highlight from 'flarum/common/helpers/highlight';
import classList from 'flarum/common/utils/classList';
import extractText from 'flarum/common/utils/extractText';
import KeyboardNavigatable from 'flarum/forum/utils/KeyboardNavigatable';
import Stream from 'flarum/common/utils/Stream';
import Discussion from 'flarum/common/models/Discussion';

import tagLabel from '../helpers/tagLabel';
import tagIcon from '../helpers/tagIcon';
import sortTags from '../utils/sortTags';

import Tag from '../models/Tag';
import AdminPage from 'flarum/admin/components/AdminPage';
import { IPageAttrs } from 'flarum/common/components/Page';

/**
 * Defines how the `SelectTagsModal` will save its state to the database.
 */
export interface ITagSelectorSaveFormat {
  separation: 'json' | 'csv';
  values: 'id' | 'slug';
}

export interface ISelectTagModalAttrs extends IInternalModalAttrs {
  /**
   * Event handler triggered when the user finishes tag selection.
   */
  onsubmit?: (tags: Tag[]) => {};
  /**
   * Whether multiple tags can be selected in the component.
   */
  multiselect?: boolean;
  /**
   * An array of tags that are selected.
   */
  value?: Tag[];
  /**
   * Filter function which the full list of tags are passed through which
   * can be used to hide certain tags.
   */
  tagFiler?: (tag: Tag) => boolean;
}

interface ISelectTagModalInternalAttrs extends ISelectTagModalAttrs {
  __fromBuildSettingsComponent: true;
  adminPage: AdminPage<IPageAttrs>;
  settingsKey: string;
  /**
   * `false` to disable automatically saving tags to the database.
   *
   * Otherwise, provide a valid object matching the `ITagSelectorSaveFormat` interface.
   */
  saveFormat: ITagSelectorSaveFormat;
}

export default class SelectTagsModal<InternalUsage extends boolean = false> extends Modal<
  InternalUsage extends true ? ISelectTagModalInternalAttrs : ISelectTagModalAttrs
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

  navigator = new KeyboardNavigatable();

  oninit(vnode) {
    super.oninit(vnode);

    // Ensure save formatting options are set
    if ('__fromBuildSettingsComponent' in this.attrs) {
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

    // this.navigator
    //   .onUp(() => this.setIndex(this.getCurrentNumericIndex() - 1, true))
    //   .onDown(() => this.setIndex(this.getCurrentNumericIndex() + 1, true))
    //   .onSelect(this.select.bind(this))
    //   .onRemove(() => this.selected.splice(this.selected.length - 1, 1));

    app.tagList.load(['parent']).then(() => {
      this.tagsLoading = false;

      const tags = sortTags(getSelectableTags(this.attrs.discussion));
      this.tags = tags;

      const discussionTags = this.attrs.discussion?.tags();
      if (this.attrs.selectedTags) {
        this.attrs.selectedTags.map(this.addTag.bind(this));
      } else if (discussionTags) {
        discussionTags.forEach((tag) => tag && this.addTag(tag));
      }

      this.selectedTag = tags[0];

      m.redraw();
    });
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

  primaryCount() {
    return this.selected.filter((tag) => tag.isPrimary()).length;
  }

  secondaryCount() {
    return this.selected.filter((tag) => !tag.isPrimary()).length;
  }

  /**
   * Add the given tag to the list of selected tags.
   */
  addTag(tag: Tag) {
    if (!tag.canStartDiscussion()) return;

    // If this tag has a parent, we'll also need to add the parent tag to the
    // selected list if it's not already in there.
    const parent = tag.parent();
    if (parent && !this.selected.includes(parent)) {
      this.selected.push(parent);
    }

    if (!this.selected.includes(tag)) {
      this.selected.push(tag);
    }
  }

  /**
   * Remove the given tag from the list of selected tags.
   */
  removeTag(tag: Tag) {
    const index = this.selected.indexOf(tag);
    if (index !== -1) {
      this.selected.splice(index, 1);

      // Look through the list of selected tags for any tags which have the tag
      // we just removed as their parent. We'll need to remove them too.
      this.selected.filter((selected) => selected.parent() === tag).forEach(this.removeTag.bind(this));
    }
  }

  className() {
    return 'ChooseTagsModal';
  }

  title() {
    // return this.attrs.discussion
    //   ? app.translator.trans('flarum-tags.forum.choose_tags.edit_title', { title: <em>{this.attrs.discussion.title()}</em> })
    //   : app.translator.trans('flarum-tags.forum.choose_tags.title');
  }

  // getInstruction(primaryCount: number, secondaryCount: number) {
  //   if (this.bypassReqs) {
  //     return '';
  //   }

  //   if (primaryCount < this.minPrimary) {
  //     const remaining = this.minPrimary - primaryCount;
  //     return app.translator.trans('flarum-tags.forum.choose_tags.choose_primary_placeholder', { count: remaining });
  //   } else if (secondaryCount < this.minSecondary) {
  //     const remaining = this.minSecondary - secondaryCount;
  //     return app.translator.trans('flarum-tags.forum.choose_tags.choose_secondary_placeholder', { count: remaining });
  //   }

  //   return '';
  // }

  content() {
    if (this.tagsLoading || !this.tags) {
      return <LoadingIndicator />;
    }

    return <div className="Modal-body"></div>;
  }

  onsubmit(e: SubmitEvent) {
    e.preventDefault();

    const discussion = this.attrs.discussion;
    const tags = this.selected;

    if (discussion) {
      discussion.save({ relationships: { tags } }).then(() => {
        if (app.current.matches(DiscussionPage)) {
          app.current.get('stream').update();
        }
        m.redraw();
      });
    }

    if (this.attrs.onsubmit) this.attrs.onsubmit(tags);

    this.hide();
  }
}
