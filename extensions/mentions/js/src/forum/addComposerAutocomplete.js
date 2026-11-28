import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import TextEditorButton from 'flarum/common/components/TextEditorButton';
import KeyboardNavigatable from 'flarum/common/utils/KeyboardNavigatable';
import AutocompleteReader from 'flarum/common/utils/AutocompleteReader';
import { throttle } from 'flarum/common/utils/throttleDebounce';

import AutocompleteDropdown from './fragments/AutocompleteDropdown';
import MentionableModels from './mentionables/MentionableModels';

export default function addComposerAutocomplete() {
  extend('flarum/common/components/TextEditor', 'onbuild', function () {
    this.mentionsDropdown = new AutocompleteDropdown();
    // Searches resolve, and the throttle's trailing call runs, after the user may have left the mention.
    this.mentionsQuery = null;
    // Escape's keyup, clicks and caret keys re-run the input listeners, which would reopen the dropdown.
    this.mentionsDismissed = null;
    this.searchMentions = throttle(250, (mentionables, buildSuggestions, query) => {
      if (query !== this.mentionsQuery) return;

      mentionables.search().then(() => {
        if (query === this.mentionsQuery) buildSuggestions();
      });
    });
    const $editor = this.$('.TextEditor-editor').wrap('<div class="ComposerBody-mentionsWrapper"></div>');

    this.navigator = new KeyboardNavigatable();
    this.navigator
      .when(() => this.mentionsDropdown.active)
      .onUp(() => this.mentionsDropdown.navigate(-1))
      .onDown(() => this.mentionsDropdown.navigate(1))
      .onSelect(this.mentionsDropdown.complete.bind(this.mentionsDropdown))
      .onCancel(() => {
        if (this.mentionsQuery) {
          if (this.mentionsDismissed?.value !== this.value) this.mentionsDismissed = { value: this.value, mentions: [] };
          this.mentionsDismissed.mentions.push(this.mentionsQuery);
        }
        this.mentionsQuery = null;
        this.mentionsDropdown.hide();
      })
      .bindTo($editor);

    $editor.after($('<div class="ComposerBody-mentionsDropdownContainer"></div>'));
  });

  extend('flarum/common/components/TextEditor', 'onremove', function () {
    this.mentionsQuery = null;
  });

  extend('flarum/common/components/TextEditor', 'buildEditorParams', function (params) {
    let matchTyped;

    const suggestionsInputListener = () => {
      const selection = this.attrs.composer.editor.getSelectionRange();

      const cursor = selection[0];

      if (selection[1] - cursor > 0) {
        this.mentionsDropdown.hide();
        this.mentionsQuery = null;
        return;
      }

      let activeFormat = null;
      const autocompleteReader = new AutocompleteReader((character) => !!(activeFormat = app.mentionFormats.get(character)));
      const autocompleting = autocompleteReader.check(this.attrs.composer.editor.getLastNChars(30), cursor, /\S+/);

      const mentionsDropdown = this.mentionsDropdown;
      let mentionables = new MentionableModels({
        onmouseenter: function () {
          mentionsDropdown.setIndex($(this).parent().index());
        },
        onclick: (replacement) => {
          this.attrs.composer.editor.replaceBeforeCursor(autocompleting.absoluteStart - 1, replacement + ' ');
          this.mentionsDropdown.hide();
        },
      });

      this.mentionsDropdown.hide();
      this.mentionsDropdown.active = false;
      const previousQuery = this.mentionsQuery;
      this.mentionsQuery = null;
      if (this.mentionsDismissed?.value !== this.value) this.mentionsDismissed = null;

      if (autocompleting) {
        mentionables.init(activeFormat.makeMentionables());
        matchTyped = activeFormat.queryFromTyped(autocompleting.typed);

        if (!matchTyped) return;

        mentionables.typed = matchTyped;

        const dismissed = this.mentionsDismissed?.mentions.some(
          (mention) => mention.start === autocompleting.absoluteStart && mention.format === activeFormat
        );
        if (dismissed) return;

        // keyup and click re-run the listeners, usually on the same mention: keep its searches then.
        const unchanged =
          previousQuery &&
          previousQuery.start === autocompleting.absoluteStart &&
          previousQuery.typed === matchTyped &&
          previousQuery.format === activeFormat;
        const query = (this.mentionsQuery = unchanged
          ? previousQuery
          : { start: autocompleting.absoluteStart, typed: matchTyped, format: activeFormat });

        const buildSuggestions = () => {
          // If the user has started to type a mention,
          // then suggest models matching.
          const suggestions = mentionables.buildSuggestions();

          if (suggestions.length) {
            this.mentionsDropdown.items = suggestions;
            m.render(this.$('.ComposerBody-mentionsDropdownContainer')[0], this.mentionsDropdown.render());

            this.mentionsDropdown.show();
            const coordinates = this.attrs.composer.editor.getCaretCoordinates(autocompleting.absoluteStart);
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

        this.searchMentions(mentionables, buildSuggestions, query);
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
