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

    app.mentionables = new MentionableModels({
      onmouseenter: function () {
        dropdown.setIndex($(this).parent().index());
      },
      onclick: (replacement) => {
        this.attrs.composer.editor.replaceBeforeCursor(absMentionStart - 1, replacement + ' ');

        dropdown.hide();
      },
    });

    // Initialize the mentionables.
    app.mentionables.init();

    const suggestionsInputListener = () => {
      const selection = this.attrs.composer.editor.getSelectionRange();

      const cursor = selection[0];

      if (selection[1] - cursor > 0) return;

      // Search backwards from the cursor for an '@' symbol. If we find one,
      // we will want to show the autocomplete dropdown!
      const lastChunk = this.attrs.composer.editor.getLastNChars(30);
      absMentionStart = 0;
      for (let i = lastChunk.length - 1; i >= 0; i--) {
        const character = lastChunk.substr(i, 1);
        if (character === '@' && (i == 0 || /\s/.test(lastChunk.substr(i - 1, 1)))) {
          relMentionStart = i + 1;
          absMentionStart = cursor - lastChunk.length + i + 1;
          break;
        }
      }

      dropdown.hide();
      dropdown.active = false;

      if (absMentionStart) {
        app.mentionables.typed = lastChunk.substring(relMentionStart).toLowerCase();
        matchTyped = app.mentionables.typed.match(/^["|“]((?:(?!"#).)+)$/);
        app.mentionables.typed = (matchTyped && matchTyped[1]) || app.mentionables.typed;

        const buildSuggestions = () => {
          // If the user has started to type a mention,
          // then suggest models matching.
          const suggestions = app.mentionables.buildSuggestions();

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

        app.mentionables.search()?.then(buildSuggestions);
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
