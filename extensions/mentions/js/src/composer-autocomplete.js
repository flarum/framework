import { extend } from 'flarum/extension-utils';
import ComposerBody from 'flarum/components/composer-body';
import ReplyComposer from 'flarum/components/reply-composer';
import EditComposer from 'flarum/components/edit-composer';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';
import highlight from 'flarum/helpers/highlight';
import truncate from 'flarum/utils/truncate';

import AutocompleteDropdown from 'flarum-mentions/components/autocomplete-dropdown';

export default function() {
  extend(ComposerBody.prototype, 'onload', function(original, element, isInitialized, context) {
    if (isInitialized) return;

    var composer = this;
    var $container = $('<div class="mentions-dropdown-container"></div>');
    var dropdown = new AutocompleteDropdown({items: []});
    var typed;
    var mentionStart;
    var $textarea = this.$('textarea');
    var searched = [];
    var searchTimeout;

    var applySuggestion = function(replacement) {
      replacement += ' ';

      var content = composer.content();
      composer.editor.setContent(content.substring(0, mentionStart - 1)+replacement+content.substr($textarea[0].selectionStart));

      var index = mentionStart - 1 + replacement.length;
      composer.editor.setSelectionRange(index, index);

      dropdown.hide();
    };

    $textarea
      .after($container)
      .on('keydown', dropdown.navigate.bind(dropdown))
      .on('click keyup', function(e) {
        // Up, down, enter, tab, escape, left, right.
        if ([9, 13, 27, 40, 38, 37, 39].indexOf(e.which) !== -1) return;

        var cursor = this.selectionStart;

        if (this.selectionEnd - cursor > 0) return;

        // Search backwards from the cursor for an '@' symbol, without any
        // intervening whitespace. If we find one, we will want to show the
        // autocomplete dropdown!
        var value = this.value;
        mentionStart = 0;
        for (var i = cursor - 1; i >= 0; i--) {
          var character = value.substr(i, 1);
          if (/\s/.test(character)) break;
          if (character == '@') {
            mentionStart = i + 1;
            break;
          }
        }

        dropdown.hide();
        dropdown.active(false);

        if (mentionStart) {
          typed = value.substring(mentionStart, cursor).toLowerCase();

          var makeSuggestion = function(user, replacement, content, className) {
            return m('a[href=javascript:;].post-preview', {
              className,
              onclick: () => applySuggestion(replacement),
              onmouseenter: function() { dropdown.setIndex($(this).parent().index()); }
            }, m('div.post-preview-content', [
              avatar(user),
              (function() {
                var vdom = username(user);
                if (typed) {
                  vdom.children[0] = highlight(vdom.children[0], typed);
                }
                return vdom;
              })(), ' ',
              content
            ]));
          };

          var buildSuggestions = () => {
            var suggestions = [];

            // If the user is replying to a discussion, or if they are editing a
            // post, then we can suggest other posts in the discussion to mention.
            // We will add the 5 most recent comments in the discussion which
            // match any username characters that have been typed.
            var composerPost = composer.props.post;
            var discussion = (composerPost && composerPost.discussion()) || composer.props.discussion;
            if (discussion) {
              discussion.posts()
                .filter(post => post && post.contentType() === 'comment' && (!composerPost || post.number() < composerPost.number()))
                .sort((a, b) => b.time() - a.time())
                .filter(post => {
                  var user = post.user();
                  return user && user.username().toLowerCase().substr(0, typed.length) === typed;
                })
                .splice(0, 5)
                .forEach(post => {
                  var user = post.user();
                  suggestions.push(
                    makeSuggestion(user, '@'+user.username()+'#'+post.number(), [
                      'Reply to #', post.number(), ' — ',
                      truncate(post.contentPlain(), 200)
                    ], 'suggestion-post')
                  );
                });
            }

            // If the user has started to type a username, then suggest users
            // matching that username.
            if (typed) {
              app.store.all('users').forEach(user => {
                if (user.username().toLowerCase().substr(0, typed.length) !== typed) return;

                suggestions.push(
                  makeSuggestion(user, '@'+user.username(), '', 'suggestion-user')
                );
              });
            }

            if (suggestions.length) {
              dropdown.props.items = suggestions;
              m.render($container[0], dropdown.view());

              dropdown.show();
              var coordinates = getCaretCoordinates(this, mentionStart);
              var left = coordinates.left;
              var top = coordinates.top + 15;
              var width = dropdown.$().outerWidth();
              var height = dropdown.$().outerHeight();
              var parent = dropdown.$().offsetParent();
              if (top + height > parent.height()) {
                top = coordinates.top - height - 15;
              }
              if (left + width > parent.width()) {
                left = parent.width() - width;
              }
              dropdown.show(left, top);
            }
          };

          buildSuggestions();

          dropdown.setIndex(0);
          dropdown.$().scrollTop(0);
          dropdown.active(true);

          clearTimeout(searchTimeout);
          if (typed) {
            searchTimeout = setTimeout(function() {
              var typedLower = typed.toLowerCase();
              if (searched.indexOf(typedLower) === -1) {
                app.store.find('users', {q: typed, page: {limit: 5}}).then(users => {
                  if (dropdown.active()) buildSuggestions();
                });
                searched.push(typedLower);
              }
            }, 250);
          }
        }
      });
  });
}
