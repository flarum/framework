/* jshint browser: true */

(function () {

// The properties that we copy into a mirrored div.
// Note that some browsers, such as Firefox,
// do not concatenate properties, i.e. padding-top, bottom etc. -> padding,
// so we have to do every single property specifically.
var properties = [
  'direction',  // RTL support
  'boxSizing',
  'width',  // on Chrome and IE, exclude the scrollbar, so the mirror div wraps exactly as the textarea does
  'height',
  'overflowX',
  'overflowY',  // copy the scrollbar for IE

  'borderTopWidth',
  'borderRightWidth',
  'borderBottomWidth',
  'borderLeftWidth',
  'borderStyle',

  'paddingTop',
  'paddingRight',
  'paddingBottom',
  'paddingLeft',

  // https://developer.mozilla.org/en-US/docs/Web/CSS/font
  'fontStyle',
  'fontVariant',
  'fontWeight',
  'fontStretch',
  'fontSize',
  'fontSizeAdjust',
  'lineHeight',
  'fontFamily',

  'textAlign',
  'textTransform',
  'textIndent',
  'textDecoration',  // might not make a difference, but better be safe

  'letterSpacing',
  'wordSpacing',

  'tabSize',
  'MozTabSize'

];

var isFirefox = window.mozInnerScreenX != null;

function getCaretCoordinates(element, position) {
  // mirrored div
  var div = document.createElement('div');
  div.id = 'input-textarea-caret-position-mirror-div';
  document.body.appendChild(div);

  var style = div.style;
  var computed = window.getComputedStyle? getComputedStyle(element) : element.currentStyle;  // currentStyle for IE < 9

  // default textarea styles
  style.whiteSpace = 'pre-wrap';
  if (element.nodeName !== 'INPUT')
    style.wordWrap = 'break-word';  // only for textarea-s

  // position off-screen
  style.position = 'absolute';  // required to return coordinates properly
  style.visibility = 'hidden';  // not 'display: none' because we want rendering

  // transfer the element's properties to the div
  properties.forEach(function (prop) {
    style[prop] = computed[prop];
  });

  if (isFirefox) {
    // Firefox lies about the overflow property for textareas: https://bugzilla.mozilla.org/show_bug.cgi?id=984275
    if (element.scrollHeight > parseInt(computed.height))
      style.overflowY = 'scroll';
  } else {
    style.overflow = 'hidden';  // for Chrome to not render a scrollbar; IE keeps overflowY = 'scroll'
  }

  div.textContent = element.value.substring(0, position);
  // the second special handling for input type="text" vs textarea: spaces need to be replaced with non-breaking spaces - http://stackoverflow.com/a/13402035/1269037
  if (element.nodeName === 'INPUT')
    div.textContent = div.textContent.replace(/\s/g, "\u00a0");

  var span = document.createElement('span');
  // Wrapping must be replicated *exactly*, including when a long word gets
  // onto the next line, with whitespace at the end of the line before (#7).
  // The  *only* reliable way to do that is to copy the *entire* rest of the
  // textarea's content into the <span> created at the caret position.
  // for inputs, just '.' would be enough, but why bother?
  span.textContent = element.value.substring(position) || '.';  // || because a completely empty faux span doesn't render at all
  div.appendChild(span);

  var coordinates = {
    top: span.offsetTop + parseInt(computed['borderTopWidth']),
    left: span.offsetLeft + parseInt(computed['borderLeftWidth'])
  };

  document.body.removeChild(div);

  return coordinates;
}

if (typeof module != "undefined" && typeof module.exports != "undefined") {
  module.exports = getCaretCoordinates;
} else {
  window.getCaretCoordinates = getCaretCoordinates;
}

}());
;
System.register('flarum/mentions/addComposerAutocomplete', ['flarum/extend', 'flarum/components/ComposerBody', 'flarum/helpers/avatar', 'flarum/helpers/username', 'flarum/helpers/highlight', 'flarum/utils/string', 'flarum/mentions/components/AutocompleteDropdown'], function (_export) {
  /*global getCaretCoordinates*/

  'use strict';

  var extend, ComposerBody, avatar, usernameHelper, highlight, truncate, AutocompleteDropdown;

  _export('default', addComposerAutocomplete);

  function addComposerAutocomplete() {
    extend(ComposerBody.prototype, 'config', function (original, isInitialized) {
      if (isInitialized) return;

      var composer = this;
      var $container = $('<div class="ComposerBody-mentionsDropdownContainer"></div>');
      var dropdown = new AutocompleteDropdown({ items: [] });
      var $textarea = this.$('textarea');
      var searched = [];
      var mentionStart = undefined;
      var typed = undefined;
      var searchTimeout = undefined;

      var applySuggestion = function applySuggestion(replacement) {
        var insert = replacement + ' ';

        var content = composer.content();
        composer.editor.setValue(content.substring(0, mentionStart - 1) + insert + content.substr($textarea[0].selectionStart));

        var index = mentionStart - 1 + insert.length;
        composer.editor.setSelectionRange(index, index);

        dropdown.hide();
      };

      $textarea.after($container).on('keydown', dropdown.navigate.bind(dropdown)).on('click keyup', function (e) {
        var _this = this;

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
          if (character === '@') {
            mentionStart = i + 1;
            break;
          }
        }

        dropdown.hide();
        dropdown.active = false;

        if (mentionStart) {
          (function () {
            typed = value.substring(mentionStart, cursor).toLowerCase();

            var makeSuggestion = function makeSuggestion(user, replacement, content) {
              var className = arguments.length <= 3 || arguments[3] === undefined ? '' : arguments[3];

              var username = usernameHelper(user);
              if (typed) {
                username.children[0] = highlight(username.children[0], typed);
              }

              return m(
                'button',
                { className: 'PostPreview ' + className,
                  onclick: function () {
                    return applySuggestion(replacement);
                  },
                  onmouseenter: function () {
                    dropdown.setIndex($(this).parent().index());
                  } },
                m(
                  'span',
                  { className: 'PostPreview-content' },
                  avatar(user),
                  username,
                  ' ',
                  ' ',
                  content
                )
              );
            };

            var buildSuggestions = function buildSuggestions() {
              var suggestions = [];

              // If the user is replying to a discussion, or if they are editing a
              // post, then we can suggest other posts in the discussion to mention.
              // We will add the 5 most recent comments in the discussion which
              // match any username characters that have been typed.
              var composerPost = composer.props.post;
              var discussion = composerPost && composerPost.discussion() || composer.props.discussion;
              if (discussion) {
                discussion.posts().filter(function (post) {
                  return post && post.contentType() === 'comment' && (!composerPost || post.number() < composerPost.number());
                }).sort(function (a, b) {
                  return b.time() - a.time();
                }).filter(function (post) {
                  var user = post.user();
                  return user && user.username().toLowerCase().substr(0, typed.length) === typed;
                }).splice(0, 5).forEach(function (post) {
                  var user = post.user();
                  suggestions.push(makeSuggestion(user, '@' + user.username() + '#' + post.id(), [app.translator.trans('flarum-mentions.forum.composer.reply_to_post_text', { number: post.number() }), ' — ', truncate(post.contentPlain(), 200)], 'MentionsDropdown-post'));
                });
              }

              // If the user has started to type a username, then suggest users
              // matching that username.
              if (typed) {
                app.store.all('users').forEach(function (user) {
                  if (user.username().toLowerCase().substr(0, typed.length) !== typed) return;

                  suggestions.push(makeSuggestion(user, '@' + user.username(), '', 'MentionsDropdown-user'));
                });
              }

              if (suggestions.length) {
                dropdown.props.items = suggestions;
                m.render($container[0], dropdown.render());

                dropdown.show();
                var coordinates = getCaretCoordinates(_this, mentionStart);
                var width = dropdown.$().outerWidth();
                var height = dropdown.$().outerHeight();
                var _parent = dropdown.$().offsetParent();
                var left = coordinates.left;
                var _top = coordinates.top + 15;
                if (_top + height > _parent.height()) {
                  _top = coordinates.top - height - 15;
                }
                if (left + width > _parent.width()) {
                  left = _parent.width() - width;
                }
                dropdown.show(left, _top);
              }
            };

            buildSuggestions();

            dropdown.setIndex(0);
            dropdown.$().scrollTop(0);
            dropdown.active = true;

            clearTimeout(searchTimeout);
            if (typed) {
              searchTimeout = setTimeout(function () {
                var typedLower = typed.toLowerCase();
                if (searched.indexOf(typedLower) === -1) {
                  app.store.find('users', { q: typed, page: { limit: 5 } }).then(function () {
                    if (dropdown.active) buildSuggestions();
                  });
                  searched.push(typedLower);
                }
              }, 250);
            }
          })();
        }
      });
    });
  }

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumComponentsComposerBody) {
      ComposerBody = _flarumComponentsComposerBody['default'];
    }, function (_flarumHelpersAvatar) {
      avatar = _flarumHelpersAvatar['default'];
    }, function (_flarumHelpersUsername) {
      usernameHelper = _flarumHelpersUsername['default'];
    }, function (_flarumHelpersHighlight) {
      highlight = _flarumHelpersHighlight['default'];
    }, function (_flarumUtilsString) {
      truncate = _flarumUtilsString.truncate;
    }, function (_flarumMentionsComponentsAutocompleteDropdown) {
      AutocompleteDropdown = _flarumMentionsComponentsAutocompleteDropdown['default'];
    }],
    execute: function () {}
  };
});;
System.register('flarum/mentions/addMentionedByList', ['flarum/extend', 'flarum/Model', 'flarum/models/Post', 'flarum/components/CommentPost', 'flarum/components/PostPreview', 'flarum/helpers/punctuateSeries', 'flarum/helpers/username', 'flarum/helpers/icon'], function (_export) {
  'use strict';

  var extend, Model, Post, CommentPost, PostPreview, punctuateSeries, username, icon;

  _export('default', addMentionedByList);

  function addMentionedByList() {
    Post.prototype.mentionedBy = Model.hasMany('mentionedBy');

    extend(CommentPost.prototype, 'footerItems', function (items) {
      var _this = this;

      var post = this.props.post;
      var replies = post.mentionedBy();

      if (replies && replies.length) {
        var _ret = (function () {
          // If there is only one reply, and it's adjacent to this post, we don't
          // really need to show the list.
          if (replies.length === 1 && replies[0].number() === post.number() + 1) {
            return {
              v: undefined
            };
          }

          var hidePreview = function hidePreview() {
            _this.$('.Post-mentionedBy-preview').removeClass('in').one('transitionend', function () {
              $(this).hide();
            });
          };

          var config = function config(element, isInitialized) {
            if (isInitialized) return;

            var $this = $(element);
            var timeout = undefined;

            var $preview = $('<ul class="Dropdown-menu Post-mentionedBy-preview fade"/>');
            $this.append($preview);

            $this.children().hover(function () {
              clearTimeout(timeout);
              timeout = setTimeout(function () {
                if (!$preview.hasClass('in') && $preview.is(':visible')) return;

                // When the user hovers their mouse over the list of people who have
                // replied to the post, render a list of reply previews into a
                // popup.
                m.render($preview[0], replies.map(function (reply) {
                  return m(
                    'li',
                    { 'data-number': reply.number() },
                    PostPreview.component({
                      post: reply,
                      onclick: hidePreview
                    })
                  );
                }));
                $preview.show();
                setTimeout(function () {
                  return $preview.off('transitionend').addClass('in');
                });
              }, 500);
            }, function () {
              clearTimeout(timeout);
              timeout = setTimeout(hidePreview, 250);
            });

            // Whenever the user hovers their mouse over a particular name in the
            // list of repliers, highlight the corresponding post in the preview
            // popup.
            $this.find('.Post-mentionedBy-summary a').hover(function () {
              $preview.find('[data-number="' + $(this).data('number') + '"]').addClass('active');
            }, function () {
              $preview.find('[data-number]').removeClass('active');
            });
          };

          var users = [];
          var repliers = replies.sort(function (reply) {
            return reply.user() === app.session.user ? -1 : 0;
          }).filter(function (reply) {
            var user = reply.user();
            if (users.indexOf(user) === -1) {
              users.push(user);
              return true;
            }
          });

          var limit = 4;
          var overLimit = repliers.length > limit;

          // Create a list of unique users who have replied. So even if a user has
          // replied twice, they will only be in this array once.
          var names = repliers.slice(0, overLimit ? limit - 1 : limit).map(function (reply) {
            var user = reply.user();

            return m(
              'a',
              { href: app.route.post(reply),
                config: m.route,
                onclick: hidePreview,
                'data-number': reply.number() },
              app.session.user === user ? app.translator.trans('flarum-mentions.forum.post.you_text') : username(user)
            );
          });

          // If there are more users that we've run out of room to display, add a "x
          // others" name to the end of the list. Clicking on it will display a modal
          // with a full list of names.
          if (overLimit) {
            var count = repliers.length - names.length;

            names.push(app.translator.transChoice('flarum-mentions.forum.post.others_text', count, { count: count }));
          }

          items.add('replies', m(
            'div',
            { className: 'Post-mentionedBy', config: config },
            m(
              'span',
              { className: 'Post-mentionedBy-summary' },
              icon('reply'),
              app.translator.transChoice('flarum-mentions.forum.post.mentioned_by' + (replies[0] === app.session.user ? '_self' : '') + '_text', names.length, {
                count: names.length,
                users: punctuateSeries(names)
              })
            )
          ));
        })();

        if (typeof _ret === 'object') return _ret.v;
      }
    });
  }

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumModel) {
      Model = _flarumModel['default'];
    }, function (_flarumModelsPost) {
      Post = _flarumModelsPost['default'];
    }, function (_flarumComponentsCommentPost) {
      CommentPost = _flarumComponentsCommentPost['default'];
    }, function (_flarumComponentsPostPreview) {
      PostPreview = _flarumComponentsPostPreview['default'];
    }, function (_flarumHelpersPunctuateSeries) {
      punctuateSeries = _flarumHelpersPunctuateSeries['default'];
    }, function (_flarumHelpersUsername) {
      username = _flarumHelpersUsername['default'];
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon['default'];
    }],
    execute: function () {}
  };
});;
System.register('flarum/mentions/addPostMentionPreviews', ['flarum/extend', 'flarum/components/CommentPost', 'flarum/components/PostPreview', 'flarum/components/LoadingIndicator'], function (_export) {
  'use strict';

  var extend, CommentPost, PostPreview, LoadingIndicator;

  _export('default', addPostMentionPreviews);

  function addPostMentionPreviews() {
    extend(CommentPost.prototype, 'config', function () {
      var contentHtml = this.props.post.contentHtml();

      if (contentHtml === this.oldPostContentHtml || this.isEditing()) return;

      this.oldPostContentHtml = contentHtml;

      var parentPost = this.props.post;
      var $parentPost = this.$();

      this.$('.UserMention, .PostMention').each(function () {
        m.route.call(this, this, false, {}, { attrs: { href: this.getAttribute('href') } });
      });

      this.$('.PostMention').each(function () {
        var $this = $(this);
        var id = $this.data('id');
        var timeout = undefined;

        // Wrap the mention link in a wrapper element so that we can insert a
        // preview popup as its sibling and relatively position it.
        var $preview = $('<ul class="Dropdown-menu PostMention-preview fade"/>');
        $parentPost.append($preview);

        var getPostElement = function getPostElement() {
          return $('.PostStream-item[data-id="' + id + '"]');
        };

        var showPreview = function showPreview() {
          // When the user hovers their mouse over the mention, look for the
          // post that it's referring to in the stream, and determine if it's
          // in the viewport. If it is, we will "pulsate" it.
          var $post = getPostElement();
          var visible = false;
          if ($post.length) {
            var _top = $post.offset().top;
            var scrollTop = window.pageYOffset;
            if (_top > scrollTop && _top + $post.height() < scrollTop + $(window).height()) {
              $post.addClass('pulsate');
              visible = true;
            }
          }

          // Otherwise, we will show a popup preview of the post. If the post
          // hasn't yet been loaded, we will need to do that.
          if (!visible) {
            (function () {
              // Position the preview so that it appears above the mention.
              // (The offsetParent should be .Post-body.)
              var positionPreview = function positionPreview() {
                $preview.show().css('top', $this.offset().top - $parentPost.offset().top - $preview.outerHeight(true)).css('left', $this.offsetParent().offset().left - $parentPost.offset().left).css('max-width', $this.offsetParent().width());
              };

              var showPost = function showPost(post) {
                var discussion = post.discussion();

                m.render($preview[0], [discussion !== parentPost.discussion() ? m(
                  'li',
                  null,
                  m(
                    'span',
                    { className: 'PostMention-preview-discussion' },
                    discussion.title()
                  )
                ) : '', m(
                  'li',
                  null,
                  PostPreview.component({ post: post })
                )]);
                positionPreview();
              };

              var post = app.store.getById('posts', id);
              if (post && post.discussion()) {
                showPost(post);
              } else {
                m.render($preview[0], LoadingIndicator.component());
                app.store.find('posts', id).then(showPost);
                positionPreview();
              }

              setTimeout(function () {
                return $preview.off('transitionend').addClass('in');
              });
            })();
          }
        };

        var hidePreview = function hidePreview() {
          getPostElement().removeClass('pulsate');
          if ($preview.hasClass('in')) {
            $preview.removeClass('in').one('transitionend', function () {
              return $preview.hide();
            });
          }
        };

        $this.on('touchstart', function (e) {
          return e.preventDefault();
        });

        $this.add($preview).hover(function () {
          clearTimeout(timeout);
          timeout = setTimeout(showPreview, 250);
        }, function () {
          clearTimeout(timeout);
          getPostElement().removeClass('pulsate');
          timeout = setTimeout(hidePreview, 250);
        }).on('touchend', function (e) {
          showPreview();
          e.stopPropagation();
        });

        $(document).on('touchend', hidePreview);
      });
    });
  }

  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumComponentsCommentPost) {
      CommentPost = _flarumComponentsCommentPost['default'];
    }, function (_flarumComponentsPostPreview) {
      PostPreview = _flarumComponentsPostPreview['default'];
    }, function (_flarumComponentsLoadingIndicator) {
      LoadingIndicator = _flarumComponentsLoadingIndicator['default'];
    }],
    execute: function () {}
  };
});;
System.register('flarum/mentions/addPostReplyAction', ['flarum/extend', 'flarum/components/Button', 'flarum/components/CommentPost', 'flarum/utils/DiscussionControls'], function (_export) {
  'use strict';

  var extend, Button, CommentPost, DiscussionControls;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton['default'];
    }, function (_flarumComponentsCommentPost) {
      CommentPost = _flarumComponentsCommentPost['default'];
    }, function (_flarumUtilsDiscussionControls) {
      DiscussionControls = _flarumUtilsDiscussionControls['default'];
    }],
    execute: function () {
      _export('default', function () {
        extend(CommentPost.prototype, 'actionItems', function (items) {
          var post = this.props.post;

          if (post.isHidden() || app.session.user && !post.discussion().canReply()) return;

          function insertMention(component, quote) {
            var user = post.user();
            var mention = '@' + (user ? user.username() : post.number()) + '#' + post.id() + ' ';

            // If the composer is empty, then assume we're starting a new reply.
            // In which case we don't want the user to have to confirm if they
            // close the composer straight away.
            if (!component.content()) {
              component.props.originalContent = mention;
            }

            component.editor.insertAtCursor((component.editor.getSelectionRange()[0] > 0 ? '\n\n' : '') + (quote ? '> ' + mention + quote.trim().replace(/\n/g, '\n> ') + '\n\n' : mention));
          }

          items.add('reply', Button.component({
            className: 'Button Button--link',
            children: app.translator.trans('flarum-mentions.forum.post.reply_link'),
            onclick: function onclick() {
              var quote = window.getSelection().toString();

              var component = app.composer.component;
              if (component && component.props.post && component.props.post.discussion() === post.discussion()) {
                insertMention(component, quote);
              } else {
                DiscussionControls.replyAction.call(post.discussion()).then(function (newComponent) {
                  return insertMention(newComponent, quote);
                });
              }
            }
          }));
        });
      });
    }
  };
});;
System.register('flarum/mentions/components/AutocompleteDropdown', ['flarum/Component'], function (_export) {
  'use strict';

  var Component, AutocompleteDropdown;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent['default'];
    }],
    execute: function () {
      AutocompleteDropdown = (function (_Component) {
        babelHelpers.inherits(AutocompleteDropdown, _Component);

        function AutocompleteDropdown() {
          babelHelpers.classCallCheck(this, AutocompleteDropdown);
          babelHelpers.get(Object.getPrototypeOf(AutocompleteDropdown.prototype), 'constructor', this).apply(this, arguments);
        }

        babelHelpers.createClass(AutocompleteDropdown, [{
          key: 'init',
          value: function init() {
            this.active = false;
            this.index = 0;
            this.keyWasJustPressed = false;
          }
        }, {
          key: 'view',
          value: function view() {
            return m(
              'ul',
              { className: 'Dropdown-menu MentionsDropdown' },
              this.props.items.map(function (item) {
                return m(
                  'li',
                  null,
                  item
                );
              })
            );
          }
        }, {
          key: 'show',
          value: function show(left, top) {
            this.$().show().css({
              left: left + 'px',
              top: top + 'px'
            });
            this.active = true;
          }
        }, {
          key: 'hide',
          value: function hide() {
            this.$().hide();
            this.active = false;
          }
        }, {
          key: 'navigate',
          value: function navigate(e) {
            var _this = this;

            if (!this.active) return;

            switch (e.which) {
              case 40:case 38:
                // Down/Up
                this.keyWasJustPressed = true;
                this.setIndex(this.index + (e.which === 40 ? 1 : -1), true);
                clearTimeout(this.keyWasJustPressedTimeout);
                this.keyWasJustPressedTimeout = setTimeout(function () {
                  return _this.keyWasJustPressed = false;
                }, 500);
                e.preventDefault();
                break;

              case 13:case 9:
                // Enter/Tab
                this.$('li').eq(this.index).find('button').click();
                e.preventDefault();
                break;

              case 27:
                // Escape
                this.hide();
                e.stopPropagation();
                e.preventDefault();
                break;

              default:
              // no default
            }
          }
        }, {
          key: 'setIndex',
          value: function setIndex(index, scrollToItem) {
            if (this.keyWasJustPressed && !scrollToItem) return;

            var $dropdown = this.$();
            var $items = $dropdown.find('li');
            var rangedIndex = index;

            if (rangedIndex < 0) {
              rangedIndex = $items.length - 1;
            } else if (rangedIndex >= $items.length) {
              rangedIndex = 0;
            }

            this.index = rangedIndex;

            var $item = $items.removeClass('active').eq(rangedIndex).addClass('active');

            if (scrollToItem) {
              var dropdownScroll = $dropdown.scrollTop();
              var dropdownTop = $dropdown.offset().top;
              var dropdownBottom = dropdownTop + $dropdown.outerHeight();
              var itemTop = $item.offset().top;
              var itemBottom = itemTop + $item.outerHeight();

              var scrollTop = undefined;
              if (itemTop < dropdownTop) {
                scrollTop = dropdownScroll - dropdownTop + itemTop - parseInt($dropdown.css('padding-top'), 10);
              } else if (itemBottom > dropdownBottom) {
                scrollTop = dropdownScroll - dropdownBottom + itemBottom + parseInt($dropdown.css('padding-bottom'), 10);
              }

              if (typeof scrollTop !== 'undefined') {
                $dropdown.stop(true).animate({ scrollTop: scrollTop }, 100);
              }
            }
          }
        }]);
        return AutocompleteDropdown;
      })(Component);

      _export('default', AutocompleteDropdown);
    }
  };
});;
System.register('flarum/mentions/components/PostMentionedNotification', ['flarum/components/Notification', 'flarum/helpers/username', 'flarum/helpers/punctuateSeries'], function (_export) {
  'use strict';

  var Notification, username, punctuateSeries, PostMentionedNotification;
  return {
    setters: [function (_flarumComponentsNotification) {
      Notification = _flarumComponentsNotification['default'];
    }, function (_flarumHelpersUsername) {
      username = _flarumHelpersUsername['default'];
    }, function (_flarumHelpersPunctuateSeries) {
      punctuateSeries = _flarumHelpersPunctuateSeries['default'];
    }],
    execute: function () {
      PostMentionedNotification = (function (_Notification) {
        babelHelpers.inherits(PostMentionedNotification, _Notification);

        function PostMentionedNotification() {
          babelHelpers.classCallCheck(this, PostMentionedNotification);
          babelHelpers.get(Object.getPrototypeOf(PostMentionedNotification.prototype), 'constructor', this).apply(this, arguments);
        }

        babelHelpers.createClass(PostMentionedNotification, [{
          key: 'icon',
          value: function icon() {
            return 'reply';
          }
        }, {
          key: 'href',
          value: function href() {
            var notification = this.props.notification;
            var post = notification.subject();
            var auc = notification.additionalUnreadCount();
            var content = notification.content();

            return app.route.discussion(post.discussion(), auc ? post.number() : content && content.replyNumber);
          }
        }, {
          key: 'content',
          value: function content() {
            var notification = this.props.notification;
            var auc = notification.additionalUnreadCount();
            var user = notification.sender();

            return app.translator.transChoice('flarum-mentions.forum.notifications.post_mentioned_text', auc + 1, {
              user: user,
              username: auc ? punctuateSeries([username(user), app.translator.transChoice('flarum-mentions.forum.notifications.others_text', auc, { count: auc })]) : undefined
            });
          }
        }, {
          key: 'excerpt',
          value: function excerpt() {
            return this.props.notification.subject().contentPlain();
          }
        }]);
        return PostMentionedNotification;
      })(Notification);

      _export('default', PostMentionedNotification);
    }
  };
});;
System.register('flarum/mentions/components/UserMentionedNotification', ['flarum/components/Notification'], function (_export) {
  'use strict';

  var Notification, UserMentionedNotification;
  return {
    setters: [function (_flarumComponentsNotification) {
      Notification = _flarumComponentsNotification['default'];
    }],
    execute: function () {
      UserMentionedNotification = (function (_Notification) {
        babelHelpers.inherits(UserMentionedNotification, _Notification);

        function UserMentionedNotification() {
          babelHelpers.classCallCheck(this, UserMentionedNotification);
          babelHelpers.get(Object.getPrototypeOf(UserMentionedNotification.prototype), 'constructor', this).apply(this, arguments);
        }

        babelHelpers.createClass(UserMentionedNotification, [{
          key: 'icon',
          value: function icon() {
            return 'at';
          }
        }, {
          key: 'href',
          value: function href() {
            var post = this.props.notification.subject();

            return app.route.discussion(post.discussion(), post.number());
          }
        }, {
          key: 'content',
          value: function content() {
            var user = this.props.notification.sender();

            return app.translator.trans('flarum-mentions.forum.notifications.user_mentioned_text', { user: user });
          }
        }, {
          key: 'excerpt',
          value: function excerpt() {
            return this.props.notification.subject().contentPlain();
          }
        }]);
        return UserMentionedNotification;
      })(Notification);

      _export('default', UserMentionedNotification);
    }
  };
});;
System.register('flarum/mentions/main', ['flarum/extend', 'flarum/app', 'flarum/components/NotificationGrid', 'flarum/utils/string', 'flarum/mentions/addPostMentionPreviews', 'flarum/mentions/addMentionedByList', 'flarum/mentions/addPostReplyAction', 'flarum/mentions/addComposerAutocomplete', 'flarum/mentions/components/PostMentionedNotification', 'flarum/mentions/components/UserMentionedNotification'], function (_export) {
  'use strict';

  var extend, app, NotificationGrid, getPlainContent, addPostMentionPreviews, addMentionedByList, addPostReplyAction, addComposerAutocomplete, PostMentionedNotification, UserMentionedNotification;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumApp) {
      app = _flarumApp['default'];
    }, function (_flarumComponentsNotificationGrid) {
      NotificationGrid = _flarumComponentsNotificationGrid['default'];
    }, function (_flarumUtilsString) {
      getPlainContent = _flarumUtilsString.getPlainContent;
    }, function (_flarumMentionsAddPostMentionPreviews) {
      addPostMentionPreviews = _flarumMentionsAddPostMentionPreviews['default'];
    }, function (_flarumMentionsAddMentionedByList) {
      addMentionedByList = _flarumMentionsAddMentionedByList['default'];
    }, function (_flarumMentionsAddPostReplyAction) {
      addPostReplyAction = _flarumMentionsAddPostReplyAction['default'];
    }, function (_flarumMentionsAddComposerAutocomplete) {
      addComposerAutocomplete = _flarumMentionsAddComposerAutocomplete['default'];
    }, function (_flarumMentionsComponentsPostMentionedNotification) {
      PostMentionedNotification = _flarumMentionsComponentsPostMentionedNotification['default'];
    }, function (_flarumMentionsComponentsUserMentionedNotification) {
      UserMentionedNotification = _flarumMentionsComponentsUserMentionedNotification['default'];
    }],
    execute: function () {

      app.initializers.add('flarum-mentions', function () {
        // For every mention of a post inside a post's content, set up a hover handler
        // that shows a preview of the mentioned post.
        addPostMentionPreviews();

        // In the footer of each post, show information about who has replied (i.e.
        // who the post has been mentioned by).
        addMentionedByList();

        // Add a 'reply' control to the footer of each post. When clicked, it will
        // open up the composer and add a post mention to its contents.
        addPostReplyAction();

        // After typing '@' in the composer, show a dropdown suggesting a bunch of
        // posts or users that the user could mention.
        addComposerAutocomplete();

        app.notificationComponents.postMentioned = PostMentionedNotification;
        app.notificationComponents.userMentioned = UserMentionedNotification;

        // Add notification preferences.
        extend(NotificationGrid.prototype, 'notificationTypes', function (items) {
          items.add('postMentioned', {
            name: 'postMentioned',
            icon: 'reply',
            label: app.translator.trans('flarum-mentions.forum.settings.notify_post_mentioned_label')
          });

          items.add('userMentioned', {
            name: 'userMentioned',
            icon: 'at',
            label: app.translator.trans('flarum-mentions.forum.settings.notify_user_mentioned_label')
          });
        });

        getPlainContent.removeSelectors.push('a.PostMention');
      });
    }
  };
});