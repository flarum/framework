import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Model from 'flarum/common/Model';
import Post from 'flarum/common/models/Post';
import CommentPost from 'flarum/forum/components/CommentPost';
import Link from 'flarum/common/components/Link';
import PostPreview from 'flarum/forum/components/PostPreview';
import punctuateSeries from 'flarum/common/helpers/punctuateSeries';
import username from 'flarum/common/helpers/username';
import icon from 'flarum/common/helpers/icon';

export default function addMentionedByList() {
  Post.prototype.mentionedBy = Model.hasMany('mentionedBy');

  function hidePreview() {
    this.$('.Post-mentionedBy-preview')
      .removeClass('in')
      .one('transitionend', function () {
        $(this).hide();
      });
  }

  extend(CommentPost.prototype, 'oncreate', function () {
    let timeout;
    const post = this.attrs.post;
    const replies = post.mentionedBy();

    if (replies && replies.length) {
      const $preview = $('<ul class="Dropdown-menu Post-mentionedBy-preview fade"/>');
      this.$().append($preview);

      const $parentPost = this.$();
      const $this = this.$('.Post-mentionedBy');

      const showPreview = () => {
        if (!$preview.hasClass('in') && $preview.is(':visible')) return;

        // When the user hovers their mouse over the list of people who have
        // replied to the post, render a list of reply previews into a
        // popup.
        m.render(
          $preview[0],
          replies.map((reply) => (
            <li data-number={reply.number()}>
              {PostPreview.component({
                post: reply,
                onclick: hidePreview.bind(this),
              })}
            </li>
          ))
        );

        $preview
          .show()
          .css('top', $this.offset().top - $parentPost.offset().top + $this.outerHeight(true))
          .css('left', $this.offsetParent().offset().left - $parentPost.offset().left)
          .css('max-width', $parentPost.width());

        setTimeout(() => $preview.off('transitionend').addClass('in'));
      };

      $this.add($preview).hover(
        () => {
          clearTimeout(timeout);
          timeout = setTimeout(showPreview, 250);
        },
        () => {
          clearTimeout(timeout);
          timeout = setTimeout(hidePreview, 250);
        }
      );

      // Whenever the user hovers their mouse over a particular name in the
      // list of repliers, highlight the corresponding post in the preview
      // popup.
      this.$()
        .find('.Post-mentionedBy-summary a')
        .hover(
          function () {
            $preview.find('[data-number="' + $(this).data('number') + '"]').addClass('active');
          },
          function () {
            $preview.find('[data-number]').removeClass('active');
          }
        );
    }
  });

  extend(CommentPost.prototype, 'footerItems', function (items) {
    const post = this.attrs.post;
    const replies = post.mentionedBy();

    if (replies && replies.length) {
      const users = [];
      const repliers = replies
        .sort((reply) => (reply.user() === app.session.user ? -1 : 0))
        .filter((reply) => {
          const user = reply.user();
          if (users.indexOf(user) === -1) {
            users.push(user);
            return true;
          }
        });

      const limit = 4;
      const overLimit = repliers.length > limit;

      // Create a list of unique users who have replied. So even if a user has
      // replied twice, they will only be in this array once.
      const names = repliers.slice(0, overLimit ? limit - 1 : limit).map((reply) => {
        const user = reply.user();

        return (
          <Link href={app.route.post(reply)} onclick={hidePreview.bind(this)} data-number={reply.number()}>
            {app.session.user === user ? app.translator.trans('flarum-mentions.forum.post.you_text') : username(user)}
          </Link>
        );
      });

      // If there are more users that we've run out of room to display, add a "x
      // others" name to the end of the list. Clicking on it will display a modal
      // with a full list of names.
      if (overLimit) {
        const count = repliers.length - names.length;

        names.push(app.translator.trans('flarum-mentions.forum.post.others_text', { count }));
      }

      items.add(
        'replies',
        <div className="Post-mentionedBy">
          <span className="Post-mentionedBy-summary">
            {icon('fas fa-reply')}
            {app.translator.trans('flarum-mentions.forum.post.mentioned_by' + (repliers[0].user() === app.session.user ? '_self' : '') + '_text', {
              count: names.length,
              users: punctuateSeries(names),
            })}
          </span>
        </div>
      );
    }
  });
}
