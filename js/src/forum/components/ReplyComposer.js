import ComposerBody from './ComposerBody';
import Alert from '../../common/components/Alert';
import Button from '../../common/components/Button';
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
 * ### Props
 *
 * - All of the props of ComposerBody
 * - `discussion`
 */
export default class ReplyComposer extends ComposerBody {
  init() {
    super.init();

    this.editor.props.preview = e => {
      minimizeComposerIfFullScreen(e);

      m.route(app.route.discussion(this.props.discussion, 'reply'));
    };
  }

  static initProps(props) {
    super.initProps(props);

    props.placeholder = props.placeholder || extractText(app.translator.trans('core.forum.composer_reply.body_placeholder'));
    props.submitLabel = props.submitLabel || app.translator.trans('core.forum.composer_reply.submit_button');
    props.confirmExit = props.confirmExit || extractText(app.translator.trans('core.forum.composer_reply.discard_confirmation'));
  }

  headerItems() {
    const items = super.headerItems();
    const discussion = this.props.discussion;

    const routeAndMinimize = function(element, isInitialized) {
      if (isInitialized) return;
      $(element).on('click', minimizeComposerIfFullScreen);
      m.route.apply(this, arguments);
    };

    items.add('title', (
      <h3>
        {icon('fas fa-reply')} {' '}
        <a href={app.route.discussion(discussion)} config={routeAndMinimize}>{discussion.title()}</a>
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
        // in, then we can update the post stream and scroll to the post.
        if (app.viewingDiscussion(discussion)) {
          app.current.stream.update().then(() => app.current.stream.goToNumber(post.number()));

        } else {
          // Otherwise, we'll create an alert message to inform the user that
          // their reply has been posted, containing a button which will
          // transition to their new post when clicked.
          let alert;
          const viewButton = Button.component({
            className: 'Button Button--link',
            children: app.translator.trans('core.forum.composer_reply.view_button'),
            onclick: () => {
              m.route(app.route.post(post));
              app.alerts.dismiss(alert);
            }
          });
          app.alerts.show(
            alert = new Alert({
              type: 'success',
              message: app.translator.trans('core.forum.composer_reply.posted_message'),
              controls: [viewButton]
            })
          );
        }

        app.composer.hide();
      },
      this.loaded.bind(this)
    );
  }
}
