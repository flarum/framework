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

import tagLabel from '../../common/helpers/tagLabel';
import tagIcon from '../../common/helpers/tagIcon';
import sortTags from '../../common/utils/sortTags';
import getSelectableTags from '../utils/getSelectableTags';
import ToggleButton from './ToggleButton';
import Tag from '../../common/models/Tag';

export interface TagDiscussionModalAttrs extends IInternalModalAttrs {
  discussion?: Discussion;
  selectedTags?: Tag[];
  onsubmit?: (tags: Tag[]) => {};
}

export default class TagDiscussionModal extends Modal<TagDiscussionModalAttrs> {
  tagsLoading = true;

  selected: Tag[] = [];
  filter = Stream('');
  focused = false;

  minPrimary = app.forum.attribute<number>('minPrimaryTags');
  maxPrimary = app.forum.attribute<number>('maxPrimaryTags');
  minSecondary = app.forum.attribute<number>('minSecondaryTags');
  maxSecondary = app.forum.attribute<number>('maxSecondaryTags');

  bypassReqs = false;

  navigator = new KeyboardNavigatable();

  tags?: Tag[];

  selectedTag?: Tag;
  oninit(vnode: Mithril.Vnode<TagDiscussionModalAttrs, this>) {
    super.oninit(vnode);

    this.navigator
      .onUp(() => this.setIndex(this.getCurrentNumericIndex() - 1, true))
      .onDown(() => this.setIndex(this.getCurrentNumericIndex() + 1, true))
      .onSelect(this.select.bind(this))
      .onRemove(() => this.selected.splice(this.selected.length - 1, 1));

    app.tagList.load(['parent']).then(() => {
      this.tagsLoading = false;

      const tags = sortTags(getSelectableTags(this.attrs.discussion));
      this.tags = tags;

      const discussionTags = this.attrs.discussion?.tags()
      if (this.attrs.selectedTags) {
        this.attrs.selectedTags.map(this.addTag.bind(this));
      } else if (discussionTags) {
        discussionTags.forEach(tag => tag && this.addTag(tag));
      }

      this.selectedTag = tags[0];

      m.redraw();
    });
  }

  primaryCount() {
    return this.selected.filter(tag => tag.isPrimary()).length;
  }

  secondaryCount() {
    return this.selected.filter(tag => !tag.isPrimary()).length;
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
      this.selected
        .filter(selected => selected.parent() === tag)
        .forEach(this.removeTag.bind(this));
    }
  }

  className() {
    return 'TagDiscussionModal';
  }

  title() {
    return this.attrs.discussion
      ? app.translator.trans('flarum-tags.forum.choose_tags.edit_title', {title: <em>{this.attrs.discussion.title()}</em>})
      : app.translator.trans('flarum-tags.forum.choose_tags.title');
  }

  getInstruction(primaryCount: number, secondaryCount: number) {
    if (this.bypassReqs) {
      return '';
    }

    if (primaryCount < this.minPrimary) {
      const remaining = this.minPrimary - primaryCount;
      return app.translator.trans('flarum-tags.forum.choose_tags.choose_primary_placeholder', {count: remaining});
    } else if (secondaryCount < this.minSecondary) {
      const remaining = this.minSecondary - secondaryCount;
      return app.translator.trans('flarum-tags.forum.choose_tags.choose_secondary_placeholder', {count: remaining});
    }

    return '';
  }

  content() {
    if (this.tagsLoading || !this.tags) {
      return <LoadingIndicator />;
    }

    let tags = this.tags;
    const filter = this.filter().toLowerCase();
    const primaryCount = this.primaryCount();
    const secondaryCount = this.secondaryCount();

    // Filter out all child tags whose parents have not been selected. This
    // makes it impossible to select a child if its parent hasn't been selected.
    tags = tags.filter(tag => {
      const parent = tag.parent();
      return parent !== null && (parent === false || this.selected.includes(parent));
    });

    // If the number of selected primary/secondary tags is at the maximum, then
    // we'll filter out all other tags of that type.
    if (primaryCount >= this.maxPrimary && !this.bypassReqs) {
      tags = tags.filter(tag => !tag.isPrimary() || this.selected.includes(tag));
    }

    if (secondaryCount >= this.maxSecondary && !this.bypassReqs) {
      tags = tags.filter(tag => tag.isPrimary() || this.selected.includes(tag));
    }

    // If the user has entered text in the filter input, then filter by tags
    // whose name matches what they've entered.
    if (filter) {
      tags = tags.filter(tag => tag.name().substr(0, filter.length).toLowerCase() === filter);
    }

    if (!this.selectedTag || !tags.includes(this.selectedTag)) this.selectedTag = tags[0];

    const inputWidth = Math.max(extractText(this.getInstruction(primaryCount, secondaryCount)).length, this.filter().length);

    return [
      <div className="Modal-body">
        <div className="TagDiscussionModal-form">
          <div className="TagDiscussionModal-form-input">
            <div className={'TagsInput FormControl ' + (this.focused ? 'focus' : '')}
              onclick={() => this.$('.TagsInput input').focus()}
            >
              <span className="TagsInput-selected">
                {this.selected.map(tag =>
                  <span className="TagsInput-tag" onclick={() => {
                    this.removeTag(tag);
                    this.onready();
                  }}>
                    {tagLabel(tag)}
                  </span>
                )}
              </span>
              <input className="FormControl"
                placeholder={extractText(this.getInstruction(primaryCount, secondaryCount))}
                bidi={this.filter}
                style={{ width: inputWidth + 'ch' }}
                onkeydown={this.navigator.navigate.bind(this.navigator)}
                onfocus={() => this.focused = true}
                onblur={() => this.focused = false}/>
            </div>
          </div>
          <div className="TagDiscussionModal-form-submit App-primaryControl">
            <Button type="submit" className="Button Button--primary" disabled={!this.meetsRequirements(primaryCount, secondaryCount)} icon="fas fa-check">
              {app.translator.trans('flarum-tags.forum.choose_tags.submit_button')}
            </Button>
          </div>
        </div>
      </div>,

      <div className="Modal-footer">
        <ul className="TagDiscussionModal-list SelectTagList">
          {tags
            .filter(tag => filter || !tag.parent() || this.selected.includes(tag.parent() as Tag))
            .map(tag => (
              <li data-index={tag.id()}
                className={classList({
                  pinned: tag.position() !== null,
                  child: !!tag.parent(),
                  colored: !!tag.color(),
                  selected: this.selected.includes(tag),
                  active: this.selectedTag === tag
                })}
                style={{color: tag.color()}}
                onmouseover={() => this.selectedTag = tag}
                onclick={this.toggleTag.bind(this, tag)}
              >
                {tagIcon(tag)}
                <span className="SelectTagListItem-name">
                  {highlight(tag.name(), filter)}
                </span>
                {tag.description()
                  ? (
                    <span className="SelectTagListItem-description">
                      {tag.description()}
                    </span>
                  ) : ''}
              </li>
            ))}
        </ul>
        {!!app.forum.attribute('canBypassTagCounts') && (
          <div className="TagDiscussionModal-controls">
            <ToggleButton className="Button" onclick={() => this.bypassReqs = !this.bypassReqs} isToggled={this.bypassReqs}>
              {app.translator.trans('flarum-tags.forum.choose_tags.bypass_requirements')}
            </ToggleButton>
          </div>
        )}
      </div>
    ];
  }

  meetsRequirements(primaryCount: number, secondaryCount: number) {
    if (this.bypassReqs) {
      return true;
    }

    return primaryCount >= this.minPrimary && secondaryCount >= this.minSecondary;
  }

  toggleTag(tag: Tag) {
    // Won't happen, needed for type safety.
    if (!this.tags) return;

    if (this.selected.includes(tag)) {
      this.removeTag(tag);
    } else {
      this.addTag(tag);
    }

    if (this.filter()) {
      this.filter('');
      this.selectedTag = this.tags[0];
    }

    this.onready();
  }

  select(e: KeyboardEvent) {
    // Ctrl + Enter submits the selection, just Enter completes the current entry
    if (e.metaKey || e.ctrlKey || this.selectedTag && this.selected.includes(this.selectedTag)) {
      if (this.selected.length) {
        // The DOM submit method doesn't emit a `submit event, so we
        // simulate a manual submission so our `onsubmit` logic is run.
        this.$('button[type="submit"]').click();
      }
    } else if (this.selectedTag) {
      this.getItem(this.selectedTag)[0].dispatchEvent(new Event('click'));
    }
  }

  selectableItems() {
    return this.$('.TagDiscussionModal-list > li');
  }

  getCurrentNumericIndex() {
    if (!this.selectedTag) return -1;

    return this.selectableItems().index(
      this.getItem(this.selectedTag)
    );
  }

  getItem(selectedTag: Tag) {
    return this.selectableItems().filter(`[data-index="${selectedTag.id()}"]`);
  }

  setIndex(index: number, scrollToItem: boolean) {
    const $items = this.selectableItems();
    const $dropdown = $items.parent();

    if (index < 0) {
      index = $items.length - 1;
    } else if (index >= $items.length) {
      index = 0;
    }

    const $item = $items.eq(index);

    this.selectedTag = app.store.getById('tags', $item.attr('data-index')!);

    m.redraw();

    if (scrollToItem && this.selectedTag) {
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
        $dropdown.stop(true).animate({scrollTop}, 100);
      }
    }
  }

  onsubmit(e: SubmitEvent) {
    e.preventDefault();

    const discussion = this.attrs.discussion;
    const tags = this.selected;

    if (discussion) {
      discussion.save({relationships: {tags}})
        .then(() => {
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
