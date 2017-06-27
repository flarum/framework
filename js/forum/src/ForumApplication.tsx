import Application from './lib/Application';
import routes from './routes';
import Search from './components/Search';

export default class ForumApplication extends Application {
  /**
   * The page's search component instance.
   *
   * @type {SearchBox}
   */
  search = new Search();

  /**
   * A map of notification types to their components.
   *
   * @type {Object}
   */
  notificationComponents = {};

  /**
   * A map of post types to their components.
   *
   * @type {Object}
   */
  postComponents = {};

  //app.postComponents.comment = CommentPost;
  //app.postComponents.discussionRenamed = DiscussionRenamedPost;

  // app.notificationComponents.discussionRenamed = DiscussionRenamedNotification;

  /**
   * @inheritdoc
   */
  registerDefaultRoutes(router) {
    routes(router);
  }

  // TODO: work out where to put these helper functions
  // /**
  //  * Check whether or not the user is currently composing a reply to a
  //  * discussion.
  //  *
  //  * @param {Discussion} discussion
  //  * @return {Boolean}
  //  */
  // composingReplyTo(discussion) {
  //   return this.composer.component instanceof ReplyComposer &&
  //     this.composer.component.props.discussion === discussion &&
  //     this.composer.position !== Composer.PositionEnum.HIDDEN;
  // }
//
  // /**
  //  * Check whether or not the user is currently viewing a discussion.
  //  *
  //  * @param {Discussion} discussion
  //  * @return {Boolean}
  //  */
  // viewingDiscussion(discussion) {
  //   return this.current instanceof DiscussionPage &&
  //     this.current.discussion === discussion;
  // }
//
  // /**
  //  * Callback for when an external authenticator (social login) action has
  //  * completed.
  //  *
  //  * If the payload indicates that the user has been logged in, then the page
  //  * will be reloaded. Otherwise, a SignUpModal will be opened, prefilled
  //  * with the provided details.
  //  *
  //  * @param {Object} payload A dictionary of props to pass into the sign up
  //  *     modal. A truthy `authenticated` prop indicates that the user has logged
  //  *     in, and thus the page is reloaded.
  //  * @public
  //  */
  // authenticationComplete(payload) {
  //   if (payload.authenticated) {
  //     window.location.reload();
  //   } else {
  //     const modal = new SignUpModal(payload);
  //     this.modal.show(modal);
  //     modal.$('[name=password]').focus();
  //   }
  // }
}
