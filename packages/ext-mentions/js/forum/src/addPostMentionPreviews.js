import { extend } from 'flarum/extend';
import CommentPost from 'flarum/components/CommentPost';
import PostPreview from 'flarum/components/PostPreview';
import LoadingIndicator from 'flarum/components/LoadingIndicator';

export default function addPostMentionPreviews() {
  extend(CommentPost.prototype, 'config', function() {
    const contentHtml = this.props.post.contentHtml();

    if (contentHtml === this.oldPostContentHtml) return;

    this.oldPostContentHtml = contentHtml;

    const discussion = this.props.post.discussion();

    this.$('.UserMention, .PostMention').each(function() {
      m.route.call(this, this, false, {}, {attrs: {href: this.getAttribute('href')}});
    });

    this.$('.PostMention').each(function() {
      const $this = $(this);
      const number = $this.data('number');
      let timeout;

      // Wrap the mention link in a wrapper element so that we can insert a
      // preview popup as its sibling and relatively position it.
      const $preview = $('<ul class="Dropdown-menu PostMention-preview fade"/>');
      const $wrapper = $('<span class="PostMention-wrapper"/>');
      $this.wrap($wrapper).after($preview);

      const getPostElement = () => {
        return $(`.PostStream-item[data-number="${number}"]`);
      };

      const showPreview = () => {
        // When the user hovers their mouse over the mention, look for the
        // post that it's referring to in the stream, and determine if it's
        // in the viewport. If it is, we will "pulsate" it.
        const $post = getPostElement();
        let visible = false;
        if ($post.length) {
          const top = $post.offset().top;
          const scrollTop = window.pageYOffset;
          if (top > scrollTop && top + $post.height() < scrollTop + $(window).height()) {
            $post.addClass('pulsate');
            visible = true;
          }
        }

        // Otherwise, we will show a popup preview of the post. If the post
        // hasn't yet been loaded, we will need to do that.
        if (!visible) {
          // Position the preview so that it appears above the mention.
          // (The offsetParent should be .Post-body.)
          const positionPreview = () => {
            $preview.show().css('top', $this.offset().top - $this.offsetParent().offset().top - $preview.outerHeight(true));
          };

          const showPost = post => {
            m.render($preview[0], <li>{PostPreview.component({post})}</li>);
            positionPreview();
          };

          const post = discussion.posts().filter(p => p && p.number() === number)[0];
          if (post) {
            showPost(post);
          } else {
            m.render($preview[0], LoadingIndicator.component());
            app.store.find('posts', {
              filter: {discussion: discussion.id(), number}
            }).then(posts => showPost(posts[0]));
            positionPreview();
          }

          setTimeout(() => $preview.off('transitionend').addClass('in'));
        }
      };

      const hidePreview = () => {
        getPostElement().removeClass('pulsate');
        if ($preview.hasClass('in')) {
          $preview.removeClass('in').one('transitionend', () => $preview.hide());
        }
      };

      $this.parent().hover(
        () => {
          clearTimeout(timeout);
          timeout = setTimeout(showPreview, 500);
        },
        () => {
          clearTimeout(timeout);
          getPostElement().removeClass('pulsate');
          timeout = setTimeout(hidePreview, 250);
        }
      )
        .on('touchstart', e => e.preventDefault())
        .on('touchend', e => {
          showPreview();
          e.stopPropagation();
        });
      $(document).on('touchend', hidePreview);
    });
  });
}
