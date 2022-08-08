import app from 'flarum/forum/app';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import classList from 'flarum/common/utils/classList';
import extractText from 'flarum/common/utils/extractText';

import getSelectableTags from '../utils/getSelectableTags';
import TagSelectionModal, { ITagSelectionModalAttrs } from '../../common/components/TagSelectionModal';

import type Discussion from 'flarum/common/models/Discussion';
import type Tag from '../../common/models/Tag';

export interface TagDiscussionModalAttrs extends ITagSelectionModalAttrs {
  discussion?: Discussion;
}

export default class TagDiscussionModal extends TagSelectionModal<TagDiscussionModalAttrs> {
  static initAttrs(attrs: TagDiscussionModalAttrs) {
    super.initAttrs(attrs);

    const title = attrs.discussion
      ? app.translator.trans('flarum-tags.forum.choose_tags.edit_title', { title: <em>{attrs.discussion.title()}</em> })
      : app.translator.trans('flarum-tags.forum.choose_tags.title');

  getInstruction(primaryCount: number, secondaryCount: number) {
    if (this.bypassReqs) {
      return null;
    }

    const suppliedOnsubmit = attrs.onsubmit || null;

    return null;
  }

      if (discussion) {
        discussion.save({ relationships: { tags } }).then(() => {
          if (app.current.matches(DiscussionPage)) {
            app.current.get('stream').update();
          }

    let tags = this.tags;
    const filter = this.filter().toLowerCase();
    const primaryCount = this.primaryCount();
    const secondaryCount = this.secondaryCount();

    // Filter out all child tags whose parents have not been selected. This
    // makes it impossible to select a child if its parent hasn't been selected.
    tags = tags.filter((tag) => {
      const parent = tag.parent();
      return parent !== null && (parent === false || this.selected.includes(parent));
    });

    // If the number of selected primary/secondary tags is at the maximum, then
    // we'll filter out all other tags of that type.
    if (primaryCount >= this.maxPrimary && !this.bypassReqs) {
      tags = tags.filter((tag) => !tag.isPrimary() || this.selected.includes(tag));
    }

    if (secondaryCount >= this.maxSecondary && !this.bypassReqs) {
      tags = tags.filter((tag) => tag.isPrimary() || this.selected.includes(tag));
    }

    // If the user has entered text in the filter input, then filter by tags
    // whose name matches what they've entered.
    if (filter) {
      tags = tags.filter((tag) => tag.name().substr(0, filter.length).toLowerCase() === filter);
    }

    if (!this.selectedTag || !tags.includes(this.selectedTag)) this.selectedTag = tags[0];

    const inputWidth = Math.max(extractText(this.getInstruction(primaryCount, secondaryCount)).length, this.filter().length);

    return (
      <>
        <div className="Modal-body">
          <div className="TagDiscussionModal-form">
            <div className="TagDiscussionModal-form-input">
              <div className={classList('TagsInput FormControl', { focus: this.focused })} onclick={() => this.$('.TagsInput input').focus()}>
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
            <div className="TagDiscussionModal-form-submit App-primaryControl">
              <Button
                type="submit"
                className="Button Button--primary"
                disabled={!this.meetsRequirements(primaryCount, secondaryCount)}
                icon="fas fa-check"
              >
                {app.translator.trans('flarum-tags.forum.choose_tags.submit_button')}
              </Button>
            </div>
          </div>
        </div>

        <div className="Modal-footer">
          <ul className="TagDiscussionModal-list SelectTagList">
            {tags
              .filter((tag) => filter || !tag.parent() || this.selected.includes(tag.parent() as Tag))
              .map((tag) => (
                <li
                  data-index={tag.id()}
                  className={classList({
                    pinned: tag.position() !== null,
                    child: !!tag.parent(),
                    colored: !!tag.color(),
                    selected: this.selected.includes(tag),
                    active: this.selectedTag === tag,
                  })}
                  style={{ color: tag.color() }}
                  onmouseover={() => (this.selectedTag = tag)}
                  onclick={this.toggleTag.bind(this, tag)}
                >
                  {tagIcon(tag)}
                  <span className="SelectTagListItem-name">{highlight(tag.name(), filter)}</span>
                  {!!tag.description() && <span className="SelectTagListItem-description">{tag.description()}</span>}
                </li>
              ))}
          </ul>
          {!!app.forum.attribute('canBypassTagCounts') && (
            <div className="TagDiscussionModal-controls">
              <ToggleButton className="Button" onclick={() => (this.bypassReqs = !this.bypassReqs)} isToggled={this.bypassReqs}>
                {app.translator.trans('flarum-tags.forum.choose_tags.bypass_requirements')}
              </ToggleButton>
            </div>
          )}
        </div>
      </>
    );
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
    if (e.metaKey || e.ctrlKey || (this.selectedTag && this.selected.includes(this.selectedTag))) {
      if (this.selected.length) {
        // The DOM submit method doesn't emit a `submit event, so we
        // simulate a manual submission so our `onsubmit` logic is run.
        this.$('button[type="submit"]').click();
      }

      if (suppliedOnsubmit) suppliedOnsubmit(tags);
    };
  }
}
