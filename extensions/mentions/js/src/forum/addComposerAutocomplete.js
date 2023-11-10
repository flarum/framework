import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import TextEditorButton from 'flarum/common/components/TextEditorButton';
import KeyboardNavigatable from 'flarum/common/utils/KeyboardNavigatable';

import AutocompleteDropdown from './fragments/AutocompleteDropdown';
import MentionableModels from './mentionables/MentionableModels';

export default function addComposerAutocomplete() {
  extend('flarum/common/components/TextEditor', 'onbuild', function () {
    this.mentionsDropdown = new AutocompleteDropdown();
    const $editor = this.$('.TextEditor-editor').wrap('<div class="ComposerBody-mentionsWrapper"></div>');

    this.navigator = new KeyboardNavigatable();
    this.navigator
      .when(() => this.mentionsDropdown.active)
      .onUp(() => this.mentionsDropdown.navigate(-1))
      .onDown(() => this.mentionsDropdown.navigate(1))
      .onSelect(this.mentionsDropdown.complete.bind(this.mentionsDropdown))
      .onCancel(this.mentionsDropdown.hide.bind(this.mentionsDropdown))
      .bindTo($editor);

    $editor.after($('<div class="ComposerBody-mentionsDropdownContainer"></div>'));
  });

  extend('flarum/common/components/TextEditor', 'buildEditorParams', function (params) {
    let relMentionStart;
    let absMentionStart;
    let matchTyped;

    let mentionables = new MentionableModels({
      onmouseenter: function () {
        this.mentionsDropdown.setIndex($(this).parent().index());
      },
      onclick: (replacement) => {
        this.attrs.composer.editor.replaceBeforeCursor(absMentionStart - 1, replacement + ' ');

        this.mentionsDropdown.hide();
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

      this.mentionsDropdown.hide();
      this.mentionsDropdown.active = false;

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
            this.mentionsDropdown.items = suggestions;
            m.render(this.$('.ComposerBody-mentionsDropdownContainer')[0], this.mentionsDropdown.render());

            this.mentionsDropdown.show();
            const coordinates = this.attrs.composer.editor.getCaretCoordinates(absMentionStart);
            const width = this.mentionsDropdown.$().outerWidth();
            const height = this.mentionsDropdown.$().outerHeight();
            const parent = this.mentionsDropdown.$().offsetParent();
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

            this.mentionsDropdown.show(left, top);
          } else {
            this.mentionsDropdown.active = false;
            this.mentionsDropdown.hide();
          }
        };

        this.mentionsDropdown.active = true;

        buildSuggestions();

        this.mentionsDropdown.setIndex(0);
        this.mentionsDropdown.$().scrollTop(0);

        mentionables.search()?.then(buildSuggestions);
      }
    };

    params.inputListeners.push(suggestionsInputListener);
  });

  extend('flarum/common/components/TextEditor', 'toolbarItems', function (items) {
    items.add(
      'mention',
      <TextEditorButton onclick={() => this.attrs.composer.editor.insertAtCursor(' @')} icon="fas fa-at">
        {app.translator.trans('flarum-mentions.forum.composer.mention_tooltip')}
      </TextEditorButton>
    );
  });
}
