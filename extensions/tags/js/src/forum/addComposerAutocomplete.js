import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import TextEditor from 'flarum/common/components/TextEditor';
import TextEditorButton from 'flarum/common/components/TextEditorButton';
import highlight from 'flarum/common/helpers/highlight';
import KeyboardNavigatable from 'flarum/common/utils/KeyboardNavigatable';
import { throttle } from 'flarum/common/utils/throttleDebounce';
import Badge from 'flarum/common/components/Badge';

import AutocompleteDropdown from './fragments/AutocompleteDropdown';
import TagMentionTextGenerator from './utils/TagMentionTextGenerator';

const throttledSearch = throttle(
  250, // 250ms timeout
  function (typed, searched, returnedTags, returnedTagIds, dropdown, buildSuggestions) {
    const typedLower = typed.toLowerCase();
    if (!searched.includes(typedLower)) {
      app.store.find('tags', { filter: { q: typed }, page: { limit: 5 } }).then((results) => {
        results.forEach((u) => {
          if (!returnedTagIds.has(u.id())) {
            returnedTagIds.add(u.id());
            returnedTags.push(u);
          }
        });

        buildSuggestions();
      });

      searched.push(typedLower);
    }
  }
);

export default function addComposerAutocomplete() {
  const $container = $('<div class="ComposerBody-mentionsDropdownContainer"></div>');
  const dropdown = new AutocompleteDropdown();

  extend(TextEditor.prototype, 'oncreate', function () {
    const $editor = this.$('.TextEditor-editor').wrap('<div class="ComposerBody-mentionsWrapper"></div>');

    this.navigator = new KeyboardNavigatable();
    this.navigator
      .when(() => dropdown.active)
      .onUp(() => dropdown.navigate(-1))
      .onDown(() => dropdown.navigate(1))
      .onSelect(dropdown.complete.bind(dropdown))
      .onCancel(dropdown.hide.bind(dropdown))
      .bindTo($editor);

    $editor.after($container);
  });

  extend(TextEditor.prototype, 'buildEditorParams', function (params) {
    const searched = [];
    let relMentionStart;
    let absMentionStart;
    let typed;
    let matchTyped;

    const mentionTextGenerator = new TagMentionTextGenerator();

    // Store tags..
    const returnedTags = Array.from(app.store.all('tags'));
    const returnedTagIds = new Set(returnedTags.map((t) => t.id()));

    const applySuggestion = (replacement) => {
      this.attrs.composer.editor.replaceBeforeCursor(absMentionStart - 1, replacement + ' ');

      dropdown.hide();
    };

    params.inputListeners.push(() => {
      const selection = this.attrs.composer.editor.getSelectionRange();

      const cursor = selection[0];

      if (selection[1] - cursor > 0) return;

      // Search backwards from the cursor for an '#' symbol. If we find one,
      // we will want to show the autocomplete dropdown!
      const lastChunk = this.attrs.composer.editor.getLastNChars(30);
      absMentionStart = 0;
      for (let i = lastChunk.length - 1; i >= 0; i--) {
        const character = lastChunk.substr(i, 1);
        if (character === '#' && (i == 0 || /\s/.test(lastChunk.substr(i - 1, 1)))) {
          relMentionStart = i + 1;
          absMentionStart = cursor - lastChunk.length + i + 1;
          break;
        }
      }

      dropdown.hide();
      dropdown.active = false;

      if (absMentionStart) {
        typed = lastChunk.substring(relMentionStart).toLowerCase();
        matchTyped = typed.match(/^["|“]((?:(?!"#).)+)$/);
        typed = (matchTyped && matchTyped[1]) || typed;

        const makeTagSuggestion = function (tag, replacement, content, className = '') {
          let tagName = tag.name().toLowerCase();

          if (typed) {
            tagName = highlight(tagName, typed);
          }

          return (
            <button
              className={'PostPreview ' + className}
              onclick={() => applySuggestion(replacement)}
              onmouseenter={function () {
                dropdown.setIndex($(this).parent().index());
              }}
            >
              <span className="PostPreview-content">
                <Badge class={`Avatar Badge Badge--tag--${tag.id()} Badge-icon `} color={tag.color()} type="tag" icon={tag.icon()} />
                <span className="username">{tagName}</span>
              </span>
            </button>
          );
        };

        const tagMatches = function (tag) {
          const names = [tag.name()];

          return names.some((name) => name.toLowerCase().substr(0, typed.length) === typed);
        };

        const buildSuggestions = () => {
          const suggestions = [];

          // If the user has started to type a tag name, then suggest tags
          // matching that name.
          if (typed) {
            returnedTags.forEach((tag) => {
              if (!tagMatches(tag)) return;

              suggestions.push(makeTagSuggestion(tag, mentionTextGenerator.forTag(tag), '', 'MentionsDropdown-tag'));
            });
          }

          if (suggestions.length) {
            dropdown.items = suggestions;
            m.render($container[0], dropdown.render());

            dropdown.show();
            const coordinates = this.attrs.composer.editor.getCaretCoordinates(absMentionStart);
            const width = dropdown.$().outerWidth();
            const height = dropdown.$().outerHeight();
            const parent = dropdown.$().offsetParent();
            let left = coordinates.left;
            let top = coordinates.top + 15;

            // Keep the dropdown inside the editor.
            if (top + height > parent.height()) {
              top = coordinates.top - height - 15;
            }
            if (left + width > parent.width()) {
              left = parent.width() - width;
            }

            // Prevent the dropdown from going off screen on mobile
            top = Math.max(-(parent.offset().top - $(document).scrollTop()), top);
            left = Math.max(-parent.offset().left, left);

            dropdown.show(left, top);
          } else {
            dropdown.active = false;
            dropdown.hide();
          }
        };

        dropdown.active = true;

        buildSuggestions();

        dropdown.setIndex(0);
        dropdown.$().scrollTop(0);

        // Don't send API calls searching for users or tags until at least 2 characters have been typed.
        // This focuses the mention results on users and posts in the discussion.
        if (typed.length > 1) {
          throttledSearch(typed, searched, returnedTags, returnedTagIds, dropdown, buildSuggestions);
        }
      }
    });
  });

  extend(TextEditor.prototype, 'toolbarItems', function (items) {
    items.add(
      'mentionTag',
      <TextEditorButton onclick={() => this.attrs.composer.editor.insertAtCursor(' #')} icon="fas fa-tags">
        {app.translator.trans('flarum-tags.forum.composer.mention_tooltip')}
      </TextEditorButton>
    );
  });
}
