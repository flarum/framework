import { extend } from 'flarum/common/extend';
import TextEditorButton from 'flarum/common/components/TextEditorButton';
import KeyboardNavigatable from 'flarum/common/utils/KeyboardNavigatable';
import Tooltip from 'flarum/common/components/Tooltip';

import AutocompleteDropdown from './fragments/AutocompleteDropdown';
import getEmojiIconCode from './helpers/getEmojiIconCode';
import cdn from './cdn';

export default function addComposerAutocomplete() {
  let emojiMap = null;

  extend('flarum/common/components/TextEditor', 'oninit', function () {
    this._loaders.push(async () => await import('./emojiMap').then((m) => (emojiMap = m.default)));
    // prettier-ignore
    this.commonEmoji = [
      '😀', '😁', '😂', '😃', '😄', '😅', '😆', '😇', '😈', '😉', '😊', '😋', '😌', '😍', '😎', '😏', '😐️', '😑', '😒',
      '😓', '😔', '😕', '😖', '😗', '😘', '😙', '😚', '😛', '😜', '😝', '😞', '😟', '😠', '😡', '😢', '😣', '😤', '😥',
      '😦', '😧', '😨', '😩', '😪', '😫', '😬', '😭', '😮', '😮‍💨', '😯', '😰', '😱', '😲', '😳', '😴', '😵', '😵‍💫',
      '😶', '😶‍🌫️', '😷', '😸', '😹', '😺', '😻', '😼', '😽', '😾', '😿', '🙀', '🙁', '🙂', '🙃', '🙄',
    ];
  });

  extend('flarum/common/components/TextEditor', 'onbuild', function () {
    this.emojiDropdown = new AutocompleteDropdown();
    const $editor = this.$('.TextEditor-editor').wrap('<div class="ComposerBody-emojiWrapper"></div>');

    this.navigator = new KeyboardNavigatable();
    this.navigator
      .when(() => this.emojiDropdown.active)
      .onUp(() => this.emojiDropdown.navigate(-1))
      .onDown(() => this.emojiDropdown.navigate(1))
      .onSelect(this.emojiDropdown.complete.bind(this.emojiDropdown))
      .onCancel(this.emojiDropdown.hide.bind(this.emojiDropdown))
      .bindTo($editor);

    $editor.after($('<div class="ComposerBody-emojiDropdownContainer"></div>'));
  });

  extend('flarum/common/components/TextEditor', 'buildEditorParams', function (params) {
    const emojiKeys = Object.keys(emojiMap);

    let relEmojiStart;
    let absEmojiStart;
    let typed;

    const applySuggestion = (replacement) => {
      this.attrs.composer.editor.replaceBeforeCursor(absEmojiStart - 1, replacement + ' ');

      this.emojiDropdown.hide();
    };

    params.inputListeners.push(() => {
      const selection = this.attrs.composer.editor.getSelectionRange();

      const cursor = selection[0];

      if (selection[1] - cursor > 0) return;

      // Search backwards from the cursor for an ':' symbol. If we find
      // one and followed by a whitespace, we will want to show the
      // autocomplete dropdown!
      const lastChunk = this.attrs.composer.editor.getLastNChars(15);
      absEmojiStart = 0;
      for (let i = lastChunk.length - 1; i >= 0; i--) {
        const character = lastChunk.substr(i, 1);
        // check what user typed, emoji names only contains alphanumeric,
        // underline, '+' and '-'
        if (!/[a-z0-9]|\+|\-|_|\:/.test(character)) break;
        // make sure ':' preceded by a whitespace or newline
        if (character === ':' && (i == 0 || /\s/.test(lastChunk.substr(i - 1, 1)))) {
          relEmojiStart = i + 1;
          absEmojiStart = cursor - lastChunk.length + i + 1;
          break;
        }
      }

      this.emojiDropdown.hide();
      this.emojiDropdown.active = false;

      if (absEmojiStart) {
        typed = lastChunk.substring(relEmojiStart).toLowerCase();

        const makeSuggestion = function ({ emoji, name, code }) {
          return (
            <Tooltip text={name}>
              <button
                key={emoji}
                onclick={() => applySuggestion(emoji)}
                onmouseenter={function () {
                  this.emojiDropdown.setIndex($(this).parent().index() - 1);
                }}
              >
                <img alt={emoji} className="emoji" draggable="false" loading="lazy" src={`${cdn}72x72/${code}.png`} title={name} />
              </button>
            </Tooltip>
          );
        };

        const buildSuggestions = () => {
          const similarEmoji = [];

          // Build a regular expression to do a fuzzy match of the given input string
          const fuzzyRegexp = function (str) {
            const reEscape = new RegExp('\\(([' + '+.*?[]{}()^$|\\'.replace(/(.)/g, '\\$1') + '])\\)', 'g');
            return new RegExp('(.*)' + str.toLowerCase().replace(/(.)/g, '($1)(.*?)').replace(reEscape, '(\\$1)') + '$', 'i');
          };
          const regTyped = fuzzyRegexp(typed);

          let maxSuggestions = 40;

          const findMatchingEmojis = (matcher) => {
            for (let i = 0; i < emojiKeys.length && maxSuggestions > 0; i++) {
              const curEmoji = emojiKeys[i];

              if (similarEmoji.indexOf(curEmoji) === -1) {
                const names = emojiMap[curEmoji];
                for (let name of names) {
                  if (matcher(name, curEmoji)) {
                    --maxSuggestions;
                    similarEmoji.push(curEmoji);
                    break;
                  }
                }
              }
            }
          };

          // First, try to find all emojis starting with the given string
          findMatchingEmojis((emojiName, emoji) => {
            // If no input is provided yet, match the most common emojis.
            if (!typed) {
              return this.commonEmoji?.includes(emoji);
            }

            return emojiName.indexOf(typed) === 0;
          });

          // If there are still suggestions left, try for some fuzzy matches
          findMatchingEmojis((emojiName) => regTyped.test(emojiName));

          const suggestions = similarEmoji
            .map((emoji) => ({
              emoji,
              name: emojiMap[emoji][0],
              code: getEmojiIconCode(emoji),
            }))
            .map(makeSuggestion);

          if (suggestions.length) {
            this.emojiDropdown.items = suggestions;
            m.render(this.$('.ComposerBody-emojiDropdownContainer')[0], this.emojiDropdown.render());

            this.emojiDropdown.show();
            const coordinates = this.attrs.composer.editor.getCaretCoordinates(absEmojiStart);
            const width = this.emojiDropdown.$().outerWidth();
            const height = this.emojiDropdown.$().outerHeight();
            const parent = this.emojiDropdown.$().offsetParent();
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

            this.emojiDropdown.show(left, top);
          }
        };

        buildSuggestions();

        this.emojiDropdown.setIndex(0);
        this.emojiDropdown.$().scrollTop(0);
        this.emojiDropdown.active = true;
      }
    });
  });

  extend('flarum/common/components/TextEditor', 'toolbarItems', function (items) {
    items.add(
      'emoji',
      <TextEditorButton onclick={() => this.attrs.composer.editor.insertAtCursor(' :')} icon="far fa-smile">
        {app.translator.trans('flarum-emoji.forum.composer.emoji_tooltip')}
      </TextEditorButton>
    );
  });
}
