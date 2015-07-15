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
  static initProps(props) {
    super.initProps(props);

    props.placeholder = props.placeholder || 'Write a Reply...';
    props.submitLabel = props.submitLabel || 'Post Reply';
    props.confirmExit = props.confirmExit || 'You have not posted your reply. Do you wish to discard it?';
  }

  headerItems() {
    const items = super.headerItems();
    const discussion = this.props.discussion;

    items.add('title', (
      <h3>
        {icon('reply')} <a href={app.route.discussion(discussion)} config={m.route}>{discussion.title()}</a>
      </h3>
    ));

    return items;
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
            children: 'View',
            onclick: () => {
              m.route(app.route.post(post));
              app.alerts.dismiss(alert);
            }
          });
          app.alerts.show(
            alert = new Alert({
              type: 'success',
              message: 'Your reply was posted.',
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
