import { extend } from 'flarum/extension-utils';
import Model from 'flarum/model';
import Post from 'flarum/models/post';
import DiscussionPage from 'flarum/components/discussion-page';
import PostComment from 'flarum/components/post-comment';
import PostPreview from 'flarum/components/post-preview';
import punctuate from 'flarum/helpers/punctuate';
import username from 'flarum/helpers/username';

export default function mentionedByList() {
  Post.prototype.mentionedBy = Model.many('mentionedBy');

  extend(DiscussionPage.prototype, 'params', function(params) {
    params.include.push('posts.mentionedBy', 'posts.mentionedBy.user');
  });

  extend(PostComment.prototype, 'footerItems', function(items) {
    var replies = this.props.post.mentionedBy();
    if (replies && replies.length) {

      var hidePreview = () => {
        this.$('.mentioned-by-preview').removeClass('in').one('transitionend', function() { $(this).hide(); });
      };

      var config = function(element, isInitialized) {
        if (isInitialized) return;
        var $this = $(element);
        var timeout;

        var $preview = $('<ul class="dropdown-menu mentioned-by-preview fade"/>');
        $this.append($preview);

        $this.children().hover(function() {
          clearTimeout(timeout);
          timeout = setTimeout(function() {
            if (!$preview.hasClass('in') && $preview.is(':visible')) return;

            // When the user hovers their mouse over the list of people who have
            // replied to the post, render a list of reply previews into a
            // popup.
            m.render($preview[0], replies.map(post => {
              return m('li', {'data-number': post.number()}, PostPreview.component({post, onclick: hidePreview}));
            }));
            $preview.show();
            setTimeout(() => $preview.off('transitionend').addClass('in'));
          }, 500);
        }, function() {
          clearTimeout(timeout);
          timeout = setTimeout(hidePreview, 250);
        });

        // Whenever the user hovers their mouse over a particular name in the
        // list of repliers, highlight the corresponding post in the preview
        // popup.
        $this.find('.summary a').hover(function() {
          $preview.find('[data-number='+$(this).data('number')+']').addClass('active');
        }, function() {
          $preview.find('[data-number]').removeClass('active');
        });
      };

      // Create a list of unique users who have replied. So even if a user has
      // replied twice, they will only be in this array once.
      var used = [];
      var repliers = replies.filter(reply => {
        if (used.indexOf(reply.user().id()) === -1) {
          used.push(reply.user().id());
          return true;
        }
      });

      items.add('replies',
        m('div.mentioned-by', {config}, [
          m('span.summary', [
            punctuate(repliers.map(reply => {
              return m('a', {
                href: app.route.post(reply),
                config: m.route,
                onclick: hidePreview,
                'data-number': reply.number()
              }, [
                reply.user() === app.session.user() ? 'You' : username(reply.user())
              ])
            })),
            ' replied to this.'
          ])
        ])
      );
    }
  });
}
