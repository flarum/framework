import { extend } from 'flarum/extend';
import CommentPost from 'flarum/components/CommentPost';
import PostPreview from 'flarum/components/PostPreview';
import LoadingIndicator from 'flarum/components/LoadingIndicator';

export default function addPostMentionPreviews() {
  extend(CommentPost.prototype, 'config', function() {
    const contentHtml = this.props.post.contentHtml();

    if (contentHtml === this.oldPostContentHtml || this.isEditing()) return;

    this.oldPostContentHtml = contentHtml;

    const parentPost = this.props.post;

    this.$('.UserMention, .PostMention').each(function() {
      m.route.call(this, this, false, {}, {attrs: {href: this.getAttribute('href')}});
    });

    this.$('.PostMention').each(function() {
      const $this = $(this);
      const id = $this.data('id');
      let timeout;

      // Wrap the mention link in a wrapper element so that we can insert a
      // preview popup as its sibling and relatively position it.
      const $preview = $('<ul class="Dropdown-menu PostMention-preview fade"/>');
      const $wrapper = $('<span class="PostMention-wrapper"/>');
      $this.wrap($wrapper).after($preview);

      const getPostElement = () => {
        return $(`.PostStream-item[data-id="${id}"]`);
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
            const discussion = post.discussion();

            m.render($preview[0], [
              discussion !== parentPost.discussion()
                ? <li><span className="PostMention-preview-discussion">{discussion.title()}</span></li>
                : '',
              <li>{PostPreview.component({post})}</li>
            ]);
            positionPreview();
          };

          const post = app.store.getById('posts', id);
          if (post && post.discussion()) {
            showPost(post);
          } else {
            m.render($preview[0], LoadingIndicator.component());
            app.store.find('posts', id).then(showPost);
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

      $this.on('touchstart', e => e.preventDefault());

      $this.parent().hover(
        () => {
          clearTimeout(timeout);
          timeout = setTimeout(showPreview, 250);
        },
        () => {
          clearTimeout(timeout);
          getPostElement().removeClass('pulsate');
          timeout = setTimeout(hidePreview, 250);
        }
      )
        .on('touchend', e => {
          showPreview();
          e.stopPropagation();
        });

      $(document).on('touchend', hidePreview);
    });
  });
}
