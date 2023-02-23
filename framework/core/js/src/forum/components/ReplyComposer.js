import app from '../../forum/app';
import ComposerBody from './ComposerBody';
import Button from '../../common/components/Button';
import Link from '../../common/components/Link';
import icon from '../../common/helpers/icon';
import extractText from '../../common/utils/extractText';

function minimizeComposerIfFullScreen(e) {
  if (app.composer.isFullScreen()) {
    app.composer.minimize();
    e.stopPropagation();
  }
}

/**
 * The `ReplyComposer` component displays the composer content for replying to a
 * discussion.
 *
 * ### Attrs
 *
 * - All of the attrs of ComposerBody
 * - `discussion`
 */
export default class ReplyComposer extends ComposerBody {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.placeholder = attrs.placeholder || extractText(app.translator.trans('core.forum.composer_reply.body_placeholder'));
    attrs.submitLabel = attrs.submitLabel || app.translator.trans('core.forum.composer_reply.submit_button');
    attrs.confirmExit = attrs.confirmExit || extractText(app.translator.trans('core.forum.composer_reply.discard_confirmation'));
  }

  headerItems() {
    const items = super.headerItems();
    const discussion = this.attrs.discussion;

    items.add(
      'title',
      <h3>
        {icon('fas fa-reply')}{' '}
        <Link href={app.route.discussion(discussion)} onclick={minimizeComposerIfFullScreen}>
          {discussion.title()}
        </Link>
      </h3>
    );

    return items;
  }

  /**
   * Jump to the preview when triggered by the text editor.
   */
  jumpToPreview(e) {
    minimizeComposerIfFullScreen(e);

    m.route.set(app.route.discussion(this.attrs.discussion, 'reply'));
  }

  /**
   * Get the data to submit to the server when the reply is saved.
   *
   * @return {Record<string, unknown>}
   */
  data() {
    return {
      content: this.composer.fields.content(),
      relationships: { discussion: this.attrs.discussion },
    };
  }

  onsubmit() {
    const discussion = this.attrs.discussion;

    this.loading = true;
    m.redraw();

    const data = this.data();

    app.store
      .createRecord('posts')
      .save(data)
      .then((post) => {
        // If we're currently viewing the discussion which this reply was made
        // in, then we can update the post stream and scroll to the post.
        if (app.viewingDiscussion(discussion)) {
          const stream = app.current.get('stream');
          stream.update().then(() => stream.goToNumber(post.number()));
        } else {
          // Otherwise, we'll create an alert message to inform the user that
          // their reply has been posted, containing a button which will
          // transition to their new post when clicked.
          let alert;
          const viewButton = (
            <Button
              className="Button Button--link"
              onclick={() => {
                m.route.set(app.route.post(post));
                app.alerts.dismiss(alert);
              }}
            >
              {app.translator.trans('core.forum.composer_reply.view_button')}
            </Button>
          );
          alert = app.alerts.show(
            {
              type: 'success',
              controls: [viewButton],
            },
            app.translator.trans('core.forum.composer_reply.posted_message')
          );
        }

        this.composer.hide();
      }, this.loaded.bind(this));
  }
}
