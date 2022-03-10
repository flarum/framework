import app from '../../forum/app';
import ComposerBody from './ComposerBody';
import Button from '../../common/components/Button';
import Link from '../../common/components/Link';
import icon from '../../common/helpers/icon';

function minimizeComposerIfFullScreen(e) {
  if (app.composer.isFullScreen()) {
    app.composer.minimize();
    e.stopPropagation();
  }
}

/**
 * The `EditPostComposer` component displays the composer content for editing a
 * post. It sets the initial content to the content of the post that is being
 * edited, and adds a header control to indicate which post is being edited.
 *
 * ### Attrs
 *
 * - All of the attrs for ComposerBody
 * - `post`
 */
export default class EditPostComposer extends ComposerBody {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.submitLabel = attrs.submitLabel || app.translator.trans('core.forum.composer_edit.submit_button');
    attrs.confirmExit = attrs.confirmExit || app.translator.trans('core.forum.composer_edit.discard_confirmation');
    attrs.originalContent = attrs.originalContent || attrs.post.content();
    attrs.user = attrs.user || attrs.post.user();

    attrs.post.editedContent = attrs.originalContent;
  }

  headerItems() {
    const items = super.headerItems();
    const post = this.attrs.post;

    items.add(
      'title',
      <h3>
        {icon('fas fa-pencil-alt')}{' '}
        <Link href={app.route.discussion(post.discussion(), post.number())} onclick={minimizeComposerIfFullScreen}>
          {app.translator.trans('core.forum.composer_edit.post_link', { number: post.number(), discussion: post.discussion().title() })}
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

    m.route.set(app.route.post(this.attrs.post));
  }

  /**
   * Get the data to submit to the server when the post is saved.
   *
   * @return {Record<string, unknown>}
   */
  data() {
    return {
      content: this.composer.fields.content(),
    };
  }

  onsubmit() {
    const discussion = this.attrs.post.discussion();

    this.loading = true;

    const data = this.data();

    this.attrs.post.save(data).then((post) => {
      // If we're currently viewing the discussion which this edit was made
      // in, then we can scroll to the post.
      if (app.viewingDiscussion(discussion)) {
        app.current.get('stream').goToNumber(post.number());
      } else {
        // Otherwise, we'll create an alert message to inform the user that
        // their edit has been made, containing a button which will
        // transition to their edited post when clicked.
        let alert;
        const viewButton = Button.component(
          {
            className: 'Button Button--link',
            onclick: () => {
              m.route.set(app.route.post(post));
              app.alerts.dismiss(alert);
            },
          },
          app.translator.trans('core.forum.composer_edit.view_button')
        );
        alert = app.alerts.show(
          {
            type: 'success',
            controls: [viewButton],
          },
          app.translator.trans('core.forum.composer_edit.edited_message')
        );
      }

      this.composer.hide();
    }, this.loaded.bind(this));
  }
}
