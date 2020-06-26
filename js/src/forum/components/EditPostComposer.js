import ComposerBody from './ComposerBody';
import Alert from '../../common/components/Alert';
import Button from '../../common/components/Button';
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
 * ### Props
 *
 * - All of the props for ComposerBody
 * - `post`
 */
export default class EditPostComposer extends ComposerBody {
  init() {
    super.init();

    this.editor.props.preview = (e) => {
      minimizeComposerIfFullScreen(e);

      m.route(app.route.post(this.props.post));
    };
  }

  static initProps(props) {
    super.initProps(props);

    props.submitLabel = props.submitLabel || app.translator.trans('core.forum.composer_edit.submit_button');
    props.confirmExit = props.confirmExit || app.translator.trans('core.forum.composer_edit.discard_confirmation');
    props.originalContent = props.originalContent || props.post.content();
    props.user = props.user || props.post.user();

    props.post.editedContent = props.originalContent;
  }

  headerItems() {
    const items = super.headerItems();
    const post = this.props.post;

    const routeAndMinimize = function (element, isInitialized) {
      if (isInitialized) return;
      $(element).on('click', minimizeComposerIfFullScreen);
      m.route.apply(this, arguments);
    };

    items.add(
      'title',
      <h3>
        {icon('fas fa-pencil-alt')}{' '}
        <a href={app.route.discussion(post.discussion(), post.number())} config={routeAndMinimize}>
          {app.translator.trans('core.forum.composer_edit.post_link', { number: post.number(), discussion: post.discussion().title() })}
        </a>
      </h3>
    );

    return items;
  }

  /**
   * Get the data to submit to the server when the post is saved.
   *
   * @return {Object}
   */
  data() {
    return {
      content: this.content(),
    };
  }

  onsubmit() {
    const discussion = this.props.post.discussion();

    this.loading = true;

    const data = this.data();

    this.props.post.save(data).then((post) => {
      // If we're currently viewing the discussion which this edit was made
      // in, then we can scroll to the post.
      if (app.viewingDiscussion(discussion)) {
        app.current.stream.goToNumber(post.number());
      } else {
        // Otherwise, we'll create an alert message to inform the user that
        // their edit has been made, containing a button which will
        // transition to their edited post when clicked.
        let alert;
        const viewButton = Button.component({
          className: 'Button Button--link',
          children: app.translator.trans('core.forum.composer_edit.view_button'),
          onclick: () => {
            m.route(app.route.post(post));
            app.alerts.dismiss(alert);
          },
        });
        app.alerts.show(
          (alert = new Alert({
            type: 'success',
            children: app.translator.trans('core.forum.composer_edit.edited_message'),
            controls: [viewButton],
          }))
        );
      }

      app.composer.hide();
    }, this.loaded.bind(this));
  }
}
