import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import TextEditor from 'flarum/common/components/TextEditor';
import TextEditorButton from 'flarum/common/components/TextEditorButton';
import ReplyComposer from 'flarum/forum/components/ReplyComposer';
import EditPostComposer from 'flarum/forum/components/EditPostComposer';
import avatar from 'flarum/common/helpers/avatar';
import usernameHelper from 'flarum/common/helpers/username';
import highlight from 'flarum/common/helpers/highlight';
import KeyboardNavigatable from 'flarum/forum/utils/KeyboardNavigatable';
import { truncate } from 'flarum/common/utils/string';
import { throttle } from 'flarum/common/utils/throttleDebounce';

import AutocompleteDropdown from './fragments/AutocompleteDropdown';
import getMentionText from './utils/getMentionText';

const throttledSearch = throttle(
  250, // 250ms timeout
  function (typed, searched, returnedUsers, returnedUserIds, dropdown, buildSuggestions) {
    const typedLower = typed.toLowerCase();
    if (!searched.includes(typedLower)) {
      app.store.find('users', { filter: { q: typed }, page: { limit: 5 } }).then((results) => {
        results.forEach((u) => {
          if (!returnedUserIds.has(u.id())) {
            returnedUserIds.add(u.id());
            returnedUsers.push(u);
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

    // We store users returned from an API here to preserve order in which they are returned
    // This prevents the user list jumping around while users are returned.
    // We also use a hashset for user IDs to provide O(1) lookup for the users already in the list.
    const returnedUsers = Array.from(app.store.all('users'));
    const returnedUserIds = new Set(returnedUsers.map((u) => u.id()));

    const applySuggestion = (replacement) => {
      this.attrs.composer.editor.replaceBeforeCursor(absMentionStart - 1, replacement + ' ');

      dropdown.hide();
    };

    params.inputListeners.push(() => {
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
        typed = lastChunk.substring(relMentionStart).toLowerCase();
        matchTyped = typed.match(/^["|“]((?:(?!"#).)+)$/);
        typed = (matchTyped && matchTyped[1]) || typed;

        const makeSuggestion = function (user, replacement, content, className = '') {
          const username = usernameHelper(user);

          if (typed) {
            username.children = [highlight(username.text, typed)];
            delete username.text;
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
                {avatar(user)}
                {username} {content}
              </span>
            </button>
          );
        };

        const userMatches = function (user) {
          const names = [user.username(), user.displayName()];

          return names.some((name) => name.toLowerCase().substr(0, typed.length) === typed);
        };

        const buildSuggestions = () => {
          const suggestions = [];

          // If the user has started to type a username, then suggest users
          // matching that username.
          if (typed) {
            returnedUsers.forEach((user) => {
              if (!userMatches(user)) return;

              suggestions.push(makeSuggestion(user, getMentionText(user), '', 'MentionsDropdown-user'));
            });
          }

          // If the user is replying to a discussion, or if they are editing a
          // post, then we can suggest other posts in the discussion to mention.
          // We will add the 5 most recent comments in the discussion which
          // match any username characters that have been typed.
          if (this.attrs.composer.bodyMatches(ReplyComposer) || this.attrs.composer.bodyMatches(EditPostComposer)) {
            const composerAttrs = this.attrs.composer.body.attrs;
            const composerPost = composerAttrs.post;
            const discussion = (composerPost && composerPost.discussion()) || composerAttrs.discussion;

            if (discussion) {
              discussion
                .posts()
                // Filter to only comment posts, and replies before this message
                .filter((post) => post && post.contentType() === 'comment' && (!composerPost || post.number() < composerPost.number()))
                // Sort by new to old
                .sort((a, b) => b.createdAt() - a.createdAt())
                // Filter to where the user matches what is being typed
                .filter((post) => {
                  const user = post.user();
                  return user && userMatches(user);
                })
                // Get the first 5
                .splice(0, 5)
                // Make the suggestions
                .forEach((post) => {
                  const user = post.user();
                  suggestions.push(
                    makeSuggestion(
                      user,
                      getMentionText(user, post.id()),
                      [
                        app.translator.trans('flarum-mentions.forum.composer.reply_to_post_text', { number: post.number() }),
                        ' — ',
                        truncate(post.contentPlain(), 200),
                      ],
                      'MentionsDropdown-post'
                    )
                  );
                });
            }
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

        // Don't send API calls searching for users until at least 2 characters have been typed.
        // This focuses the mention results on users and posts in the discussion.
        if (typed.length > 1 && app.forum.attribute('canSearchUsers')) {
          throttledSearch(typed, searched, returnedUsers, returnedUserIds, dropdown, buildSuggestions);
        }
      }
    });
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
