import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import TextEditor from 'flarum/common/components/TextEditor';
import TextEditorButton from 'flarum/common/components/TextEditorButton';
import KeyboardNavigatable from 'flarum/common/utils/KeyboardNavigatable';

import AutocompleteDropdown from './fragments/AutocompleteDropdown';
import MentionableModels from './mentionables/MentionableModels';

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
    let relMentionStart;
    let absMentionStart;
    let matchTyped;

    let mentionables = new MentionableModels({
      onmouseenter: function () {
        dropdown.setIndex($(this).parent().index());
      },
      onclick: (replacement) => {
        this.attrs.composer.editor.replaceBeforeCursor(absMentionStart - 1, replacement + ' ');

        dropdown.hide();
      },
    });

    const suggestionsInputListener = () => {
      const selection = this.attrs.composer.editor.getSelectionRange();

      const cursor = selection[0];

      if (selection[1] - cursor > 0) return;

      // Search backwards from the cursor for a mention triggering symbol. If we find one,
      // we will want to show the correct autocomplete dropdown!
      // Check classes implementing the IMentionableModel interface to see triggering symbols.
      const lastChunk = this.attrs.composer.editor.getLastNChars(30);
      absMentionStart = 0;
      let activeFormat = null;
      for (let i = lastChunk.length - 1; i >= 0; i--) {
        const character = lastChunk.substr(i, 1);
        activeFormat = app.mentionFormats.get(character);

        if (activeFormat && (i === 0 || /\s/.test(lastChunk.substr(i - 1, 1)))) {
          relMentionStart = i + 1;
          absMentionStart = cursor - lastChunk.length + i + 1;
          mentionables.init(activeFormat.makeMentionables());
          break;
        }
      }

      dropdown.hide();
      dropdown.active = false;

      if (absMentionStart) {
        const typed = lastChunk.substring(relMentionStart).toLowerCase();
        matchTyped = activeFormat.queryFromTyped(typed);

        if (!matchTyped) return;

        mentionables.typed = matchTyped;

        const buildSuggestions = () => {
          // If the user has started to type a mention,
          // then suggest models matching.
          const suggestions = mentionables.buildSuggestions();

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

        mentionables.search()?.then(buildSuggestions);
      }
    };

    params.inputListeners.push(suggestionsInputListener);
  });

  extend(TextEditor.prototype, 'toolbarItems', function (items) {
    items.add(
      'mention',
      <TextEditorButton onclick={() => this.attrs.composer.editor.insertAtCursor(' @')} icon="fas fa-at">
        {app.translator.trans('flarum-mentions.forum.composer.mention_tooltip')}
      </TextEditorButton>
    );
  });
}
