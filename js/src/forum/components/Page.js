import Page from '../../common/components/Page';

/**
 * The `ForumPage` component
 *
 * @abstract
 */
export default class ForumPage extends Page {
  init() {
    super.init();

    app.modal.close();
  }
}
