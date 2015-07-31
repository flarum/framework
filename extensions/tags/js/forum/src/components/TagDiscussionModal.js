import Modal from 'flarum/components/Modal';
import DiscussionPage from 'flarum/components/DiscussionPage';
import Button from 'flarum/components/Button';
import highlight from 'flarum/helpers/highlight';
import classList from 'flarum/utils/classList';

import tagLabel from 'tags/helpers/tagLabel';
import tagIcon from 'tags/helpers/tagIcon';
import sortTags from 'tags/utils/sortTags';

export default class TagDiscussionModal extends Modal {
  constructor(...args) {
    super(...args);

    this.tags = sortTags(app.store.all('tags').filter(tag => tag.canStartDiscussion()));

    this.selected = [];
    this.filter = m.prop('');
    this.index = this.tags[0].id();
    this.focused = false;

    if (this.props.selectedTags) {
      this.props.selectedTags.map(this.addTag.bind(this));
    } else if (this.props.discussion) {
      this.props.discussion.tags().map(this.addTag.bind(this));
    }
  }

  /**
   * Add the given tag to the list of selected tags.
   *
   * @param {Tag} tag
   */
  addTag(tag) {
    if (!tag.canStartDiscussion()) return;

    // If this tag has a parent, we'll also need to add the parent tag to the
    // selected list if it's not already in there.
    const parent = tag.parent();
    if (parent) {
      const index = this.selected.indexOf(parent);
      if (index === -1) {
        this.selected.push(parent);
      }
    }

    this.selected.push(tag);
  }

  /**
   * Remove the given tag from the list of selected tags.
   *
   * @param {Tag} tag
   */
  removeTag(tag) {
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
    return this.props.discussion
      ? app.trans('tags.edit_discussion_tags_title', {title: <em>{this.props.discussion.title()}</em>})
      : app.trans('tags.tag_new_discussion_title');
  }

  content() {
    let tags = this.tags;
    const filter = this.filter().toLowerCase();

    if (filter) {
      tags = tags.filter(tag => tag.name().substr(0, filter.length).toLowerCase() === filter);
    }

    if (tags.indexOf(this.index) === -1) {
      this.index = tags[0];
    }

    return [
      <div className="Modal-body">
        <div className="TagDiscussionModal-form">
          <div className="TagDiscussionModal-form-input">
            <div className={'TagsInput FormControl ' + (this.focused ? 'focus' : '')}>
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
                placeholder={!this.selected.length ? app.trans('tags.discussion_tags_placeholder') : ''}
                value={this.filter()}
                oninput={m.withAttr('value', this.filter)}
                onkeydown={this.onkeydown.bind(this)}
                onfocus={() => this.focused = true}
                onblur={() => this.focused = false}/>
            </div>
          </div>
          <div className="TagDiscussionModal-form-submit App-primaryControl">
            {Button.component({
              type: 'submit',
              className: 'Button Button--primary',
              disabled: !this.selected.length,
              icon: 'check',
              children: app.trans('tags.confirm')
            })}
          </div>
        </div>
      </div>,

      <div className="Modal-footer">
        <ul className="TagDiscussionModal-list SelectTagList">
          {tags.map(tag => {
            if (!filter && tag.parent() && this.selected.indexOf(tag.parent()) === -1) {
              return '';
            }

            return (
              <li data-index={tag.id()}
                className={classList({
                  pinned: tag.position() !== null,
                  child: !!tag.parent(),
                  colored: !!tag.color(),
                  selected: this.selected.indexOf(tag) !== -1,
                  active: this.index === tag
                })}
                style={{color: tag.color()}}
                onmouseover={() => this.index = tag}
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
            );
          })}
        </ul>
      </div>
    ];
  }

  toggleTag(tag) {
    const index = this.selected.indexOf(tag);

    if (index !== -1) {
      this.removeTag(tag);
    } else {
      this.addTag(tag);
    }

    if (this.filter()) {
      this.filter('');
      this.index = this.tags[0];
    }

    this.onready();
  }

  onkeydown(e) {
    switch (e.which) {
      case 40:
      case 38: // Down/Up
        e.preventDefault();
        this.setIndex(this.getCurrentNumericIndex() + (e.which === 40 ? 1 : -1), true);
        break;

      case 13: // Return
        e.preventDefault();
        if (e.metaKey || e.ctrlKey || this.selected.indexOf(this.index) !== -1) {
          if (this.selected.length) {
            this.$('form').submit();
          }
        } else {
          this.getItem(this.index)[0].dispatchEvent(new Event('click'));
        }
        break;

      case 8: // Backspace
        if (e.target.selectionStart === 0 && e.target.selectionEnd === 0) {
          e.preventDefault();
          this.selected.splice(this.selected.length - 1, 1);
        }
        break;

      default:
        // no default
    }
  }

  selectableItems() {
    return this.$('.TagDiscussionModal-list > li');
  }

  getCurrentNumericIndex() {
    return this.selectableItems().index(
      this.getItem(this.index)
    );
  }

  getItem(index) {
    return this.selectableItems().filter(`[data-index="${index.id()}"]`);
  }

  setIndex(index, scrollToItem) {
    const $items = this.selectableItems();
    const $dropdown = $items.parent();

    if (index < 0) {
      index = $items.length - 1;
    } else if (index >= $items.length) {
      index = 0;
    }

    const $item = $items.eq(index);

    this.index = app.store.getById('tags', $item.attr('data-index'));

    m.redraw();

    if (scrollToItem) {
      const dropdownScroll = $dropdown.scrollTop();
      const dropdownTop = $dropdown.offset().top;
      const dropdownBottom = dropdownTop + $dropdown.outerHeight();
      const itemTop = $item.offset().top;
      const itemBottom = itemTop + $item.outerHeight();

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

  onsubmit(e) {
    e.preventDefault();

    const discussion = this.props.discussion;
    const tags = this.selected;

    if (discussion) {
      discussion.save({relationships: {tags}})
        .then(() => {
          if (app.current instanceof DiscussionPage) {
            app.current.stream.update();
          }
          m.redraw();
        });
    }

    if (this.props.onsubmit) this.props.onsubmit(tags);

    app.modal.close();

    m.redraw.strategy('none');
  }
}
