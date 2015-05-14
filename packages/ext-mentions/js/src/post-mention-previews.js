import { extend } from 'flarum/extension-utils';
import PostComment from 'flarum/components/post-comment';
import PostPreview from 'flarum/components/post-preview';
import LoadingIndicator from 'flarum/components/loading-indicator';

export default function postMentionPreviews() {
  extend(PostComment.prototype, 'config', function() {
    var contentHtml = this.props.post.contentHtml();
    if (contentHtml === this.oldPostContentHtml) return;
    this.oldPostContentHtml = contentHtml;

    var discussion = this.props.post.discussion();

    this.$('.mention-post').each(function() {
      var $this = $(this);
      var number = $this.data('number');
      var timeout;

      // Wrap the mention link in a wrapper element so that we can insert a
      // preview popup as its sibling and relatively position it.
      var $preview = $('<ul class="dropdown-menu mention-post-preview fade"/>');
      var $wrapper = $('<span class="mention-post-wrapper"/>');
      $this.wrap($wrapper).after($preview);

      var getPostElement = function() {
        return $('.discussion-posts .item[data-number='+number+']');
      };

      $this.parent().hover(
        function() {
          clearTimeout(timeout);
          timeout = setTimeout(function() {
            // When the user hovers their mouse over the mention, look for the
            // post that it's referring to in the stream, and determine if it's
            // in the viewport. If it is, we will "pulsate" it.
            var $post = getPostElement();
            var visible = false;
            if ($post.length) {
              var top = $post.offset().top;
              var scrollTop = window.pageYOffset;
              if (top > scrollTop && top + $post.height() < scrollTop + $(window).height()) {
                $post.addClass('pulsate');
                visible = true;
              }
            }

            // Otherwise, we will show a popup preview of the post. If the post
            // hasn't yet been loaded, we will need to do that.
            if (!visible) {
              var showPost = function(post) {
                m.render($preview[0], m('li', PostPreview.component({post})));
              }

              var post = discussion.posts().filter(post => post && post.number() == number)[0];
              if (post) {
                showPost(post);
              } else {
                m.render($preview[0], LoadingIndicator.component());
                app.store.find('posts', {discussions: discussion.id(), number}).then(posts => showPost(posts[0]));
              }

              // Position the preview so that it appears above the mention.
              // (The offsetParent should be .post-body.)
              $preview.show().css('top', $this.offset().top - $this.offsetParent().offset().top - $preview.outerHeight(true));
              setTimeout(() => $preview.off('transitionend').addClass('in'));
            }
          }, 500);
        },
        function() {
          clearTimeout(timeout);
          getPostElement().removeClass('pulsate');
          timeout = setTimeout(() => {
            if ($preview.hasClass('in')) {
              $preview.removeClass('in').one('transitionend', () => $preview.hide());
            }
          }, 250);
        }
      );
    });
  });
}
