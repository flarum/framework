import FormModal from 'flarum/components/form-modal';
import DiscussionPage from 'flarum/components/discussion-page';
import highlight from 'flarum/helpers/highlight';
import classList from 'flarum/utils/class-list';

import tagLabel from 'flarum-tags/helpers/tag-label';
import tagIcon from 'flarum-tags/helpers/tag-icon';
import sortTags from 'flarum-tags/utils/sort-tags';

export default class TagDiscussionModal extends FormModal {
  constructor(props) {
    super(props);

    this.tags = sortTags(app.store.all('tags'));

    this.selected = m.prop([]);
    if (this.props.selectedTags) {
      this.props.selectedTags.map(this.addTag.bind(this));
    } else if (this.props.discussion) {
      this.props.discussion.tags().map(this.addTag.bind(this));
    }

    this.filter = m.prop('');

    this.index = m.prop(this.tags[0].id());

    this.focused = m.prop(false);
  }

  addTag(tag) {
    var selected = this.selected();
    var parent = tag.parent();
    if (parent) {
      var index = selected.indexOf(parent);
      if (index === -1) {
        selected.push(parent);
      }
    }
    selected.push(tag);
  }

  removeTag(tag) {
    var selected = this.selected();
    var index = selected.indexOf(tag);
    selected.splice(index, 1);
    selected.filter(selected => selected.parent() && selected.parent() === tag).forEach(child => {
      var index = selected.indexOf(child);
      selected.splice(index, 1);
    });
  }

  view() {
    var discussion = this.props.discussion;
    var selected = this.selected();

    var tags = this.tags;
    var filter = this.filter().toLowerCase();

    if (filter) {
      tags = tags.filter(tag => tag.name().substr(0, filter.length).toLowerCase() === filter);
    }

    if (tags.indexOf(this.index()) === -1) {
      this.index(tags[0]);
    }

    return super.view({
      className: 'tag-discussion-modal',
      title: discussion
        ? ['Edit Tags for ', m('em', discussion.title())]
        : 'Start a Discussion About...',
      body: [
        m('div.tags-form', [
          m('div.tags-input.form-control', {className: this.focused() ? 'focus' : ''}, [
            m('span.tags-input-selected', selected.map(tag =>
              m('span.remove-tag', {onclick: () => {
                this.removeTag(tag);
                this.ready();
              }}, tagLabel(tag))
            )),
            m('input.form-control', {
              placeholder: !selected.length ? 'Choose one or more topics' : '',
              value: this.filter(),
              oninput: m.withAttr('value', this.filter),
              onkeydown: this.onkeydown.bind(this),
              onfocus: () => this.focused(true),
              onblur: () => this.focused(false)
            })
          ]),
          m('button[type=submit].btn.btn-primary', {disabled: !selected.length}, 'Confirm')
        ])
      ],
      footer: [
        m('ul.tags-select', tags.map(tag =>
          filter || !tag.parent() || selected.indexOf(tag.parent()) !== -1
            ? m('li', {
              'data-index': tag.id(),
              className: classList({
                category: tag.position() !== null,
                selected: selected.indexOf(tag) !== -1,
                active: this.index() == tag
              }),
              style: {
                color: tag.color()
              },
              onmouseover: () => {
                this.index(tag);
              },
              onclick: () => {
                var selected = this.selected();
                var index = selected.indexOf(tag);
                if (index !== -1) {
                  this.removeTag(tag);
                } else {
                  this.addTag(tag);
                }
                if (this.filter()) {
                  this.filter('');
                  this.index(this.tags[0]);
                }
                this.ready();
              }
            }, [
              tagIcon(tag),
              m('span.name', highlight(tag.name(), filter)),
              tag.description() ? m('span.description', tag.description()) : ''
            ])
            : ''
        ))
      ]
    });
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
        if (e.metaKey || e.ctrlKey || this.selected().indexOf(this.index()) !== -1) {
          if (this.selected().length) {
            this.$('form').submit();
          }
        } else {
          this.getItem(this.index())[0].dispatchEvent(new Event('click'));
        }
        break;

      case 8: // Backspace
        if (e.target.selectionStart == 0 && e.target.selectionEnd == 0) {
          e.preventDefault();
          var selected = this.selected();
          selected.splice(selected.length - 1, 1);
        }
    }
  }

  selectableItems() {
    return this.$('.tags-select > li');
  }

  getCurrentNumericIndex() {
    return this.selectableItems().index(
      this.getItem(this.index())
    );
  }

  getItem(index) {
    var $items = this.selectableItems();
    return $items.filter('[data-index='+index.id()+']');
  }

  setIndex(index, scrollToItem) {
    var $items = this.selectableItems();
    var $dropdown = $items.parent();

    if (index < 0) {
      index = $items.length - 1;
    } else if (index >= $items.length) {
      index = 0;
    }

    var $item = $items.eq(index);

    this.index(app.store.getById('tags', $item.attr('data-index')));

    m.redraw();

    if (scrollToItem) {
      var dropdownScroll = $dropdown.scrollTop();
      var dropdownTop = $dropdown.offset().top;
      var dropdownBottom = dropdownTop + $dropdown.outerHeight();
      var itemTop = $item.offset().top;
      var itemBottom = itemTop + $item.outerHeight();

      var scrollTop;
      if (itemTop < dropdownTop) {
        scrollTop = dropdownScroll - dropdownTop + itemTop - parseInt($dropdown.css('padding-top'));
      } else if (itemBottom > dropdownBottom) {
        scrollTop = dropdownScroll - dropdownBottom + itemBottom + parseInt($dropdown.css('padding-bottom'));
      }

      if (typeof scrollTop !== 'undefined') {
        $dropdown.stop(true).animate({scrollTop}, 100);
      }
    }
  }

  onsubmit(e) {
    e.preventDefault();

    var discussion = this.props.discussion;
    var tags = this.selected();

    if (discussion) {
      discussion.save({links: {tags}}).then(discussion => {
        if (app.current instanceof DiscussionPage) {
          app.current.stream.sync();
        }
        m.redraw();
      });
    }

    this.props.onsubmit && this.props.onsubmit(tags);

    app.modal.close();

    m.redraw.strategy('none');
  }
}
