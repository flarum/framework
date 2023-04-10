import app from 'flarum/common/app';
import Button from 'flarum/common/components/Button';
import classList from 'flarum/common/utils/classList';
import extractText from 'flarum/common/utils/extractText';
import highlight from 'flarum/common/helpers/highlight';
import KeyboardNavigatable from 'flarum/common/utils/KeyboardNavigatable';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Modal from 'flarum/common/components/Modal';
import Stream from 'flarum/common/utils/Stream';

import sortTags from '../utils/sortTags';
import tagLabel from '../helpers/tagLabel';
import tagIcon from '../helpers/tagIcon';
import ToggleButton from '../../forum/components/ToggleButton';

import type Tag from '../models/Tag';
import type { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Mithril from 'mithril';

export interface ITagSelectionModalLimits {
  /** Whether to allow bypassing the limits set here. This will show a toggle button to bypass limits. */
  allowBypassing?: boolean;
  /** Maximum number of primary/secondary tags allowed. */
  max?: {
    total?: number;
    primary?: number;
    secondary?: number;
  };
  /** Minimum number of primary/secondary tags to be selected. */
  min?: {
    total?: number;
    primary?: number;
    secondary?: number;
  };
}

export interface ITagSelectionModalAttrs extends IInternalModalAttrs {
  /** Custom modal className to use. */
  className?: string;
  /** Modal title, defaults to 'Choose Tags'. */
  title?: string;
  /** Initial tag selection value. */
  selectedTags?: Tag[];
  /** Limits set based on minimum and maximum number of primary/secondary tags that can be selected. */
  limits?: ITagSelectionModalLimits;
  /** Whether to allow resetting the value. Defaults to true. */
  allowResetting?: boolean;
  /** Whether to require the parent tag of a selected tag to be selected as well. */
  requireParentTag?: boolean;
  /** Filter tags that can be selected. */
  selectableTags?: (tags: Tag[]) => Tag[];
  /** Whether a tag can be selected. */
  canSelect: (tag: Tag) => boolean;
  /** Callback for when a tag is selected. */
  onSelect?: (tag: Tag, selected: Tag[]) => void;
  /** Callback for when a tag is deselected. */
  onDeselect?: (tag: Tag, selected: Tag[]) => void;
  /** Callback for when the selection is submitted. */
  onsubmit?: (selected: Tag[]) => void;
}

export type ITagSelectionModalState = undefined;

export default class TagSelectionModal<
  CustomAttrs extends ITagSelectionModalAttrs = ITagSelectionModalAttrs,
  CustomState extends ITagSelectionModalState = ITagSelectionModalState
> extends Modal<CustomAttrs, CustomState> {
  protected loading = true;
  protected tags!: Tag[];
  protected selected: Tag[] = [];
  protected bypassReqs: boolean = false;

  protected filter = Stream('');
  protected focused = false;
  protected navigator = new KeyboardNavigatable();
  protected indexTag?: Tag;

  static initAttrs(attrs: ITagSelectionModalAttrs) {
    super.initAttrs(attrs);

    // Default values for optional attributes.
    attrs.title ||= extractText(app.translator.trans('flarum-tags.lib.tag_selection_modal.title'));
    attrs.canSelect ||= () => true;
    attrs.allowResetting ??= true;
    attrs.limits = {
      min: {
        total: attrs.limits?.min?.total ?? -Infinity,
        primary: attrs.limits?.min?.primary ?? -Infinity,
        secondary: attrs.limits?.min?.secondary ?? -Infinity,
      },
      max: {
        total: attrs.limits?.max?.total ?? Infinity,
        primary: attrs.limits?.max?.primary ?? Infinity,
        secondary: attrs.limits?.max?.secondary ?? Infinity,
      },
    };

    // Prevent illogical limits from being provided.
    catchInvalidLimits(attrs.limits);
  }

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.navigator
      .onUp(() => this.setIndex(this.getCurrentNumericIndex() - 1, true))
      .onDown(() => this.setIndex(this.getCurrentNumericIndex() + 1, true))
      .onSelect(this.select.bind(this))
      .onRemove(() => this.selected.splice(this.selected.length - 1, 1));

    app.tagList.load(['parent']).then((tags) => {
      this.loading = false;

      if (this.attrs.selectableTags) {
        tags = this.attrs.selectableTags(tags);
      }

      this.tags = sortTags(tags);

      if (this.attrs.selectedTags) {
        this.attrs.selectedTags.map(this.addTag.bind(this));
      }

      this.indexTag = tags[0];

      m.redraw();
    });
  }

  className() {
    return classList('TagSelectionModal', this.attrs.className);
  }

  title() {
    return this.attrs.title;
  }

  content() {
    if (this.loading || !this.tags) {
      return <LoadingIndicator />;
    }

    const filter = this.filter().toLowerCase();
    const primaryCount = this.primaryCount();
    const secondaryCount = this.secondaryCount();
    const tags = this.getFilteredTags();

    const inputWidth = Math.max(extractText(this.getInstruction(primaryCount, secondaryCount)).length, this.filter().length);

    return [
      <div className="Modal-body">
        <div className="TagSelectionModal-form">
          <div className="TagSelectionModal-form-input">
            <div className={'TagsInput FormControl ' + (this.focused ? 'focus' : '')} onclick={() => this.$('.TagsInput input').focus()}>
              <span className="TagsInput-selected">
                {this.selected.map((tag) => (
                  <span
                    className="TagsInput-tag"
                    onclick={() => {
                      this.removeTag(tag);
                      this.onready();
                    }}
                  >
                    {tagLabel(tag)}
                  </span>
                ))}
              </span>
              <input
                className="FormControl"
                placeholder={extractText(this.getInstruction(primaryCount, secondaryCount))}
                bidi={this.filter}
                style={{ width: inputWidth + 'ch' }}
                onkeydown={this.navigator.navigate.bind(this.navigator)}
                onfocus={() => (this.focused = true)}
                onblur={() => (this.focused = false)}
              />
            </div>
          </div>
          <div className="TagSelectionModal-form-submit App-primaryControl">
            <Button
              type="submit"
              className="Button Button--primary"
              disabled={!this.meetsRequirements(primaryCount, secondaryCount)}
              icon="fas fa-check"
            >
              {app.translator.trans('flarum-tags.lib.tag_selection_modal.submit_button')}
            </Button>
          </div>
        </div>
      </div>,

      <div className="Modal-footer">
        <ul className="TagSelectionModal-list SelectTagList">
          {tags.map((tag) => (
            <li
              data-index={tag.id()}
              className={classList({
                pinned: tag.position() !== null,
                child: !!tag.parent(),
                colored: !!tag.color(),
                selected: this.selected.includes(tag),
                active: this.indexTag === tag,
              })}
              style={{ color: tag.color() }}
              onmouseover={() => (this.indexTag = tag)}
              onclick={this.toggleTag.bind(this, tag)}
            >
              {tagIcon(tag)}
              <span className="SelectTagListItem-name">{highlight(tag.name(), filter)}</span>
              {tag.description() ? <span className="SelectTagListItem-description">{tag.description()}</span> : ''}
            </li>
          ))}
        </ul>
        {this.attrs.limits!.allowBypassing && (
          <div className="TagSelectionModal-controls">
            <ToggleButton className="Button" onclick={() => (this.bypassReqs = !this.bypassReqs)} isToggled={this.bypassReqs}>
              {app.translator.trans('flarum-tags.lib.tag_selection_modal.bypass_requirements')}
            </ToggleButton>
          </div>
        )}
      </div>,
    ];
  }

  /**
   * Filters the available tags on every state change.
   */
  private getFilteredTags(): Tag[] {
    const filter = this.filter().toLowerCase();
    const primaryCount = this.primaryCount();
    const secondaryCount = this.secondaryCount();
    let tags = this.tags;

    if (this.attrs.requireParentTag) {
      // Filter out all child tags whose parents have not been selected. This
      // makes it impossible to select a child if its parent hasn't been selected.
      tags = tags.filter((tag) => {
        const parent = tag.parent();
        return parent !== null && (parent === false || this.selected.includes(parent));
      });
    }

    if (!this.bypassReqs) {
      // If we reached the total maximum number of tags, we can't select anymore.
      if (this.selected.length >= this.attrs.limits!.max!.total!) {
        tags = tags.filter((tag) => this.selected.includes(tag));
      }
      // If the number of selected primary/secondary tags is at the maximum, then
      // we'll filter out all other tags of that type.
      else {
        if (primaryCount >= this.attrs.limits!.max!.primary!) {
          tags = tags.filter((tag) => !tag.isPrimary() || this.selected.includes(tag));
        }
        if (secondaryCount >= this.attrs.limits!.max!.secondary!) {
          tags = tags.filter((tag) => tag.isPrimary() || this.selected.includes(tag));
        }
      }
    }

    // If the user has entered text in the filter input, then filter by tags
    // whose name matches what they've entered.
    if (filter) {
      tags = tags.filter((tag) => tag.name().toLowerCase().includes(filter));
    }

    if (!this.indexTag || !tags.includes(this.indexTag)) this.indexTag = tags[0];

    return tags;
  }

  /**
   * Counts the number of selected primary tags.
   */
  protected primaryCount(): number {
    return this.selected.filter((tag) => tag.isPrimary()).length;
  }

  /**
   * Counts the number of selected secondary tags.
   */
  protected secondaryCount(): number {
    return this.selected.filter((tag) => !tag.isPrimary()).length;
  }

  /**
   * Validates the number of selected primary/secondary tags against the set min max limits.
   */
  protected meetsRequirements(primaryCount: number, secondaryCount: number) {
    if (this.bypassReqs || (this.attrs.allowResetting && this.selected.length === 0)) {
      return true;
    }

    if (this.selected.length < this.attrs.limits!.min!.total!) {
      return false;
    }

    return primaryCount >= this.attrs.limits!.min!.primary! && secondaryCount >= this.attrs.limits!.min!.secondary!;
  }

  /**
   * Add the given tag to the list of selected tags.
   */
  protected addTag(tag: Tag | undefined) {
    if (!tag || !this.attrs.canSelect(tag)) return;

    if (this.attrs.onSelect) {
      this.attrs.onSelect(tag, this.selected);
    }

    // If this tag has a parent, we'll also need to add the parent tag to the
    // selected list if it's not already in there.
    if (this.attrs.requireParentTag) {
      const parent = tag.parent();
      if (parent && !this.selected.includes(parent)) {
        this.selected.push(parent);
      }
    }

    if (!this.selected.includes(tag)) {
      this.selected.push(tag);
    }
  }

  /**
   * Remove the given tag from the list of selected tags.
   */
  protected removeTag(tag: Tag) {
    const index = this.selected.indexOf(tag);

    if (index !== -1) {
      this.selected.splice(index, 1);

      // Look through the list of selected tags for any tags which have the tag
      // we just removed as their parent. We'll need to remove them too.
      if (this.attrs.requireParentTag) {
        this.selected.filter((t) => t.parent() === tag).forEach(this.removeTag.bind(this));
      }

      if (this.attrs.onDeselect) {
        this.attrs.onDeselect(tag, this.selected);
      }
    }
  }

  protected toggleTag(tag: Tag) {
    // Won't happen, needed for type safety.
    if (!this.tags) return;

    if (this.selected.includes(tag)) {
      this.removeTag(tag);
    } else {
      this.addTag(tag);
    }

    if (this.filter()) {
      this.filter('');
      this.indexTag = this.tags[0];
    }

    this.onready();
  }

  /**
   * Gives human text instructions based on the current number of selected tags and set limits.
   */
  protected getInstruction(primaryCount: number, secondaryCount: number) {
    if (this.bypassReqs) {
      return '';
    }

    if (primaryCount < this.attrs.limits!.min!.primary!) {
      const remaining = this.attrs.limits!.min!.primary! - primaryCount;
      return extractText(app.translator.trans('flarum-tags.lib.tag_selection_modal.choose_primary_placeholder', { count: remaining }));
    } else if (secondaryCount < this.attrs.limits!.min!.secondary!) {
      const remaining = this.attrs.limits!.min!.secondary! - secondaryCount;
      return extractText(app.translator.trans('flarum-tags.lib.tag_selection_modal.choose_secondary_placeholder', { count: remaining }));
    } else if (this.selected.length < this.attrs.limits!.min!.total!) {
      const remaining = this.attrs.limits!.min!.total! - this.selected.length;
      return extractText(app.translator.trans('flarum-tags.lib.tag_selection_modal.choose_tags_placeholder', { count: remaining }));
    }

    return '';
  }

  /**
   * Submit tag selection.
   */
  onsubmit(e: SubmitEvent) {
    e.preventDefault();

    if (this.attrs.onsubmit) this.attrs.onsubmit(this.selected);

    this.hide();
  }

  protected select(e: KeyboardEvent) {
    // Ctrl + Enter submits the selection, just Enter completes the current entry
    if (e.metaKey || e.ctrlKey || (this.indexTag && this.selected.includes(this.indexTag))) {
      if (this.selected.length) {
        // The DOM submit method doesn't emit a `submit event, so we
        // simulate a manual submission so our `onsubmit` logic is run.
        this.$('button[type="submit"]').click();
      }
    } else if (this.indexTag) {
      this.getItem(this.indexTag)[0].dispatchEvent(new Event('click'));
    }
  }

  protected selectableItems() {
    return this.$('.TagSelectionModal-list > li');
  }

  protected getCurrentNumericIndex() {
    if (!this.indexTag) return -1;

    return this.selectableItems().index(this.getItem(this.indexTag));
  }

  protected getItem(selectedTag: Tag) {
    return this.selectableItems().filter(`[data-index="${selectedTag.id()}"]`);
  }

  protected setIndex(index: number, scrollToItem: boolean) {
    const $items = this.selectableItems();
    const $dropdown = $items.parent();

    if (index < 0) {
      index = $items.length - 1;
    } else if (index >= $items.length) {
      index = 0;
    }

    const $item = $items.eq(index);

    this.indexTag = app.store.getById('tags', $item.attr('data-index')!);

    m.redraw();

    if (scrollToItem && this.indexTag) {
      const dropdownScroll = $dropdown.scrollTop()!;
      const dropdownTop = $dropdown.offset()!.top;
      const dropdownBottom = dropdownTop + $dropdown.outerHeight()!;
      const itemTop = $item.offset()!.top;
      const itemBottom = itemTop + $item.outerHeight()!;

      let scrollTop;
      if (itemTop < dropdownTop) {
        scrollTop = dropdownScroll - dropdownTop + itemTop - parseInt($dropdown.css('padding-top'), 10);
      } else if (itemBottom > dropdownBottom) {
        scrollTop = dropdownScroll - dropdownBottom + itemBottom + parseInt($dropdown.css('padding-bottom'), 10);
      }

      if (typeof scrollTop !== 'undefined') {
        $dropdown.stop(true).animate({ scrollTop }, 100);
      }
    }
  }
}

/**
 * Catch invalid limits provided to the tag selection modal.
 */
function catchInvalidLimits(limits: ITagSelectionModalLimits) {
  if (limits.min!.primary! > limits.max!.primary!) {
    throw new Error('The minimum number of primary tags allowed cannot be more than the maximum number of primary tags allowed.');
  }

  if (limits.min!.secondary! > limits.max!.secondary!) {
    throw new Error('The minimum number of secondary tags allowed cannot be more than the maximum number of secondary tags allowed.');
  }

  if (limits.min!.total! > limits.max!.primary! + limits.max!.secondary!) {
    throw new Error('The minimum number of tags allowed cannot be more than the maximum number of primary and secondary tags allowed together.');
  }

  if (limits.max!.total! < limits.min!.primary! + limits.min!.secondary!) {
    throw new Error('The maximum number of tags allowed cannot be less than the minimum number of primary and secondary tags allowed together.');
  }

  if (limits.min!.total! > limits.max!.total!) {
    throw new Error('The minimum number of tags allowed cannot be more than the maximum number of tags allowed.');
  }
}
