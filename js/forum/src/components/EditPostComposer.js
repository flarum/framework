import ComposerBody from 'flarum/components/ComposerBody';
import icon from 'flarum/helpers/icon';

/**
 * The `EditPostComposer` component displays the composer content for editing a
 * post. It sets the initial content to the content of the post that is being
 * edited, and adds a header control to indicate which post is being edited.
 *
 * ### Props
 *
 * - All of the props for ComposerBody
 * - `post`
 */
export default class EditPostComposer extends ComposerBody {
  static initProps(props) {
    super.initProps(props);

    props.submitLabel = props.submitLabel || app.trans('core.save_changes');
    props.confirmExit = props.confirmExit || app.trans('core.confirm_discard_edit');
    props.originalContent = props.originalContent || props.post.content();
    props.user = props.user || props.post.user();

    props.post.editedContent = props.originalContent;
  }

  config(isInitialized, context) {
    super.config(isInitialized, context);

    if (isInitialized) return;

    // Every 50ms, if the content has changed, then update the post's
    // editedContent property and redraw. This will cause the preview in the
    // post's component to update.
    const updateInterval = setInterval(() => {
      const post = this.props.post;
      const content = this.content();

      if (content === post.editedContent) return;

      post.editedContent = content;
      m.redraw();
    }, 50);

    context.onunload = () => clearInterval(updateInterval);
  }

  headerItems() {
    const items = super.headerItems();
    const post = this.props.post;

    items.add('title', (
      <h3>
        {icon('pencil')}{' '}
        <a href={app.route.discussion(post.discussion(), post.number())} config={m.route}>
          {app.trans('core.editing_post', {number: post.number(), discussion: post.discussion().title()})}
        </a>
      </h3>
    ));

    return items;
  }

  /**
   * Get the data to submit to the server when the post is saved.
   *
   * @return {Object}
   */
  data() {
    return {
      content: this.content()
    };
  }

  onsubmit() {
    this.loading = true;

    const data = this.data();

    this.props.post.save(data).then(
      () => {
        app.composer.hide();
        m.redraw();
      },
      () => this.loading = false
    );
  }
}
