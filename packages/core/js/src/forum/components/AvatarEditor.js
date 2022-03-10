import app from '../../forum/app';
import Component from '../../common/Component';
import avatar from '../../common/helpers/avatar';
import icon from '../../common/helpers/icon';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import classList from '../../common/utils/classList';
import Button from '../../common/components/Button';
import LoadingIndicator from '../../common/components/LoadingIndicator';

/**
 * The `AvatarEditor` component displays a user's avatar along with a dropdown
 * menu which allows the user to upload/remove the avatar.
 *
 * ### Attrs
 *
 * - `className`
 * - `user`
 */
export default class AvatarEditor extends Component {
  oninit(vnode) {
    super.oninit(vnode);

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

  view() {
    const user = this.attrs.user;

    return (
      <div className={classList(['AvatarEditor', 'Dropdown', this.attrs.className, this.loading && 'loading', this.isDraggedOver && 'dragover'])}>
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
          {this.loading ? (
            <LoadingIndicator display="unset" size="large" />
          ) : user.avatarUrl() ? (
            icon('fas fa-pencil-alt')
          ) : (
            icon('fas fa-plus-circle')
          )}
        </a>
        <ul className="Dropdown-menu Menu">{listItems(this.controlItems().toArray())}</ul>
      </div>
    );
  }

  /**
   * Get the items in the edit avatar dropdown menu.
   *
   * @return {ItemList<import('mithril').Children>}
   */
  controlItems() {
    const items = new ItemList();

    items.add(
      'upload',
      <Button icon="fas fa-upload" onclick={this.openPicker.bind(this)}>
        {app.translator.trans('core.forum.user.avatar_upload_button')}
      </Button>
    );

    items.add(
      'remove',
      <Button icon="fas fa-times" onclick={this.remove.bind(this)}>
        {app.translator.trans('core.forum.user.avatar_remove_button')}
      </Button>
    );

    return items;
  }

  /**
   * Enable dragover style
   *
   * @param {DragEvent} e
   */
  enableDragover(e) {
    e.preventDefault();
    e.stopPropagation();
    this.isDraggedOver = true;
  }

  /**
   * Disable dragover style
   *
   * @param {DragEvent} e
   */
  disableDragover(e) {
    e.preventDefault();
    e.stopPropagation();
    this.isDraggedOver = false;
  }

  /**
   * Upload avatar when file is dropped into dropzone.
   *
   * @param {DragEvent} e
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
   * @param {MouseEvent} e
   */
  quickUpload(e) {
    if (!this.attrs.user.avatarUrl()) {
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
    const $input = $('<input type="file" accept=".jpg, .jpeg, .png, .bmp, .gif">');

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

    const user = this.attrs.user;
    const data = new FormData();
    data.append('avatar', file);

    this.loading = true;
    m.redraw();

    app
      .request({
        method: 'POST',
        url: `${app.forum.attribute('apiUrl')}/users/${user.id()}/avatar`,
        serialize: (raw) => raw,
        body: data,
      })
      .then(this.success.bind(this), this.failure.bind(this));
  }

  /**
   * Remove the user's avatar.
   */
  remove() {
    const user = this.attrs.user;

    this.loading = true;
    m.redraw();

    app
      .request({
        method: 'DELETE',
        url: `${app.forum.attribute('apiUrl')}/users/${user.id()}/avatar`,
      })
      .then(this.success.bind(this), this.failure.bind(this));
  }

  /**
   * After a successful upload/removal, push the updated user data into the
   * store, and force a recomputation of the user's avatar color.
   *
   * @param {object} response
   * @protected
   */
  success(response) {
    app.store.pushPayload(response);
    delete this.attrs.user.avatarColor;

    this.loading = false;
    m.redraw();
  }

  /**
   * If avatar upload/removal fails, stop loading.
   *
   * @param {object} response
   * @protected
   */
  failure(response) {
    this.loading = false;
    m.redraw();
  }
}
