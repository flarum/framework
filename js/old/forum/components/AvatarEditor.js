import Component from '../../common/Component';
import avatar from '../../common/helpers/avatar';
import icon from '../../common/helpers/icon';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import Button from '../../common/components/Button';
import LoadingIndicator from '../../common/components/LoadingIndicator';

/**
 * The `AvatarEditor` component displays a user's avatar along with a dropdown
 * menu which allows the user to upload/remove the avatar.
 *
 * ### Props
 *
 * - `className`
 * - `user`
 */
export default class AvatarEditor extends Component {
  init() {
    /**
     * Whether or not an avatar upload is in progress.
     *
     * @type {Boolean}
     */
    this.loading = false;

    /**
     * Whether or not an image has been dragged over the dropzone.
     *
     * @type {Boolean}
     */
    this.isDraggedOver = false;
  }

  static initProps(props) {
    super.initProps(props);

    props.className = props.className || '';
  }

  view() {
    const user = this.props.user;

    return (
      <div className={'AvatarEditor Dropdown ' + this.props.className + (this.loading ? ' loading' : '') + (this.isDraggedOver ? ' dragover' : '')}>
        {avatar(user)}
        <a
          className={user.avatarUrl() ? 'Dropdown-toggle' : 'Dropdown-toggle AvatarEditor--noAvatar'}
          title={app.translator.trans('core.forum.user.avatar_upload_tooltip')}
          data-toggle="dropdown"
          onclick={this.quickUpload.bind(this)}
          ondragover={this.enableDragover.bind(this)}
          ondragenter={this.enableDragover.bind(this)}
          ondragleave={this.disableDragover.bind(this)}
          ondragend={this.disableDragover.bind(this)}
          ondrop={this.dropUpload.bind(this)}
        >
          {this.loading ? LoadingIndicator.component() : user.avatarUrl() ? icon('fas fa-pencil-alt') : icon('fas fa-plus-circle')}
        </a>
        <ul className="Dropdown-menu Menu">{listItems(this.controlItems().toArray())}</ul>
      </div>
    );
  }

  /**
   * Get the items in the edit avatar dropdown menu.
   *
   * @return {ItemList}
   */
  controlItems() {
    const items = new ItemList();

    items.add(
      'upload',
      Button.component({
        icon: 'fas fa-upload',
        children: app.translator.trans('core.forum.user.avatar_upload_button'),
        onclick: this.openPicker.bind(this),
      })
    );

    items.add(
      'remove',
      Button.component({
        icon: 'fas fa-times',
        children: app.translator.trans('core.forum.user.avatar_remove_button'),
        onclick: this.remove.bind(this),
      })
    );

    return items;
  }

  /**
   * Enable dragover style
   *
   * @param {Event} e
   */
  enableDragover(e) {
    e.preventDefault();
    e.stopPropagation();
    this.isDraggedOver = true;
  }

  /**
   * Disable dragover style
   *
   * @param {Event} e
   */
  disableDragover(e) {
    e.preventDefault();
    e.stopPropagation();
    this.isDraggedOver = false;
  }

  /**
   * Upload avatar when file is dropped into dropzone.
   *
   * @param {Event} e
   */
  dropUpload(e) {
    e.preventDefault();
    e.stopPropagation();
    this.isDraggedOver = false;
    this.upload(e.dataTransfer.files[0]);
  }

  /**
   * If the user doesn't have an avatar, there's no point in showing the
   * controls dropdown, because only one option would be viable: uploading.
   * Thus, when the avatar editor's dropdown toggle button is clicked, we prompt
   * the user to upload an avatar immediately.
   *
   * @param {Event} e
   */
  quickUpload(e) {
    if (!this.props.user.avatarUrl()) {
      e.preventDefault();
      e.stopPropagation();
      this.openPicker();
    }
  }

  /**
   * Upload avatar using file picker
   */
  openPicker() {
    if (this.loading) return;

    // Create a hidden HTML input element and click on it so the user can select
    // an avatar file. Once they have, we will upload it via the API.
    const user = this.props.user;
    const $input = $('<input type="file">');

    $input
      .appendTo('body')
      .hide()
      .click()
      .on('input', (e) => {
        this.upload($(e.target)[0].files[0]);
      });
  }

  /**
   * Upload avatar
   *
   * @param {File} file
   */
  upload(file) {
    if (this.loading) return;

    const user = this.props.user;
    const data = new FormData();
    data.append('avatar', file);

    this.loading = true;
    m.redraw();

    app
      .request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/users/' + user.id() + '/avatar',
        serialize: (raw) => raw,
        data,
      })
      .then(this.success.bind(this), this.failure.bind(this));
  }

  /**
   * Remove the user's avatar.
   */
  remove() {
    const user = this.props.user;

    this.loading = true;
    m.redraw();

    app
      .request({
        method: 'DELETE',
        url: app.forum.attribute('apiUrl') + '/users/' + user.id() + '/avatar',
      })
      .then(this.success.bind(this), this.failure.bind(this));
  }

  /**
   * After a successful upload/removal, push the updated user data into the
   * store, and force a recomputation of the user's avatar color.
   *
   * @param {Object} response
   * @protected
   */
  success(response) {
    app.store.pushPayload(response);
    delete this.props.user.avatarColor;

    this.loading = false;
    m.redraw();
  }

  /**
   * If avatar upload/removal fails, stop loading.
   *
   * @param {Object} response
   * @protected
   */
  failure(response) {
    this.loading = false;
    m.redraw();
  }
}
