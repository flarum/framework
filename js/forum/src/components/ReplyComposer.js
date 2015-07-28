import ComposerBody from 'flarum/components/ComposerBody';
import Alert from 'flarum/components/Alert';
import Button from 'flarum/components/Button';
import icon from 'flarum/helpers/icon';

/**
 * The `ReplyComposer` component displays the composer content for replying to a
 * discussion.
 *
 * ### Props
 *
 * - All of the props of ComposerBody
 * - `discussion`
 */
export default class ReplyComposer extends ComposerBody {
  constructor(...args) {
    super(...args);

    this.editor.props.preview = () => {
      m.route(app.route.discussion(this.props.discussion, 'reply'));
    };
  }

  static initProps(props) {
    super.initProps(props);

    props.placeholder = props.placeholder || app.trans('core.write_a_reply');
    props.submitLabel = props.submitLabel || app.trans('core.post_reply');
    props.confirmExit = props.confirmExit || app.trans('core.confirm_discard_reply');
  }

  headerItems() {
    const items = super.headerItems();
    const discussion = this.props.discussion;

    items.add('title', (
      <h3>
        {icon('reply')}{' '}<a href={app.route.discussion(discussion)} config={m.route}>{discussion.title()}</a>
      </h3>
    ));

    return items;
  }

  config(isInitialized, context) {
    super.config(isInitialized, context);

    if (isInitialized) return;

    // Every 50ms, if the content has changed, then update the post's
    // editedContent property and redraw. This will cause the preview in the
    // post's component to update.
    const updateInterval = setInterval(() => {
      const discussion = this.props.discussion;
      const content = this.content();

      if (content === discussion.replyContent) return;

      discussion.replyContent = content;

      const anchorToBottom = $(window).scrollTop() + $(window).height() >= $(document).height();
      m.redraw();
      if (anchorToBottom) {
        $(window).scrollTop($(document).height());
      }
    }, 50);

    context.onunload = () => clearInterval(updateInterval);
  }

  /**
   * Get the data to submit to the server when the reply is saved.
   *
   * @return {Object}
   */
  data() {
    return {
      content: this.content(),
      relationships: {discussion: this.props.discussion}
    };
  }

  onsubmit() {
    const discussion = this.props.discussion;

    this.loading = true;
    m.redraw();

    const data = this.data();

    app.store.createRecord('posts').save(data).then(
      post => {
        // If we're currently viewing the discussion which this reply was made
        // in, then we can update the post stream.
        if (app.viewingDiscussion(discussion)) {
          app.current.stream.update();
        } else {
          // Otherwise, we'll create an alert message to inform the user that
          // their reply has been posted, containing a button which will
          // transition to their new post when clicked.
          let alert;
          const viewButton = Button.component({
            className: 'Button Button--link',
            children: app.trans('core.view'),
            onclick: () => {
              m.route(app.route.post(post));
              app.alerts.dismiss(alert);
            }
          });
          app.alerts.show(
            alert = new Alert({
              type: 'success',
              message: app.trans('core.reply_posted'),
              controls: [viewButton]
            })
          );
        }

        app.composer.hide();
      },
      errors => {
        this.loading = false;
        m.redraw();
        app.alertErrors(errors);
      }
    );
  }
}
