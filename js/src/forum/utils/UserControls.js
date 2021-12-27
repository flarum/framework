import app from '../../forum/app';
import Button from '../../common/components/Button';
import Separator from '../../common/components/Separator';
import EditUserModal from '../../common/components/EditUserModal';
import UserPage from '../components/UserPage';
import ItemList from '../../common/utils/ItemList';

/**
 * The `UserControls` utility constructs a list of buttons for a user which
 * perform actions on it.
 */
export default {
  /**
   * Get a list of controls for a user.
   *
   * @param {import('../../common/models/User').default} user
   * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
   *
   * @return {ItemList<import('mithril').Children>}
   */
  controls(user, context) {
    const items = new ItemList();

    ['user', 'moderation', 'destructive'].forEach((section) => {
      const controls = this[section + 'Controls'](user, context).toArray();
      if (controls.length) {
        controls.forEach((item) => items.add(item.itemName, item));
        items.add(section + 'Separator', <Separator />);
      }
    });

    return items;
  },

  /**
   * Get controls for a user pertaining to the current user (e.g. poke, follow).
   *
   * @param {import('../../common/models/User').default} user
   * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
   *
   * @return {ItemList<import('mithril').Children>}
   * @protected
   */
  userControls() {
    return new ItemList();
  },

  /**
   * Get controls for a user pertaining to moderation (e.g. suspend, edit).
   *
   * @param {import('../../common/models/User').default} user
   * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
   *
   * @return {ItemList<import('mithril').Children>}
   * @protected
   */
  moderationControls(user) {
    const items = new ItemList();

    if (user.canEdit() || user.canEditCredentials() || user.canEditGroups()) {
      items.add(
        'edit',
        <Button icon="fas fa-pencil-alt" onclick={this.editAction.bind(this, user)}>
          {app.translator.trans('core.forum.user_controls.edit_button')}
        </Button>
      );
    }

    return items;
  },

  /**
   * Get controls for a user which are destructive (e.g. delete).
   *
   * @param {import('../../common/models/User').default} user
   * @param {import('../../common/Component').default<any, any>}  context The parent component under which the controls menu will be displayed.
   *
   * @return {ItemList<import('mithril').Children>}
   * @protected
   */
  destructiveControls(user) {
    const items = new ItemList();

    if (user.id() !== '1' && user.canDelete()) {
      items.add(
        'delete',
        <Button icon="fas fa-times" onclick={this.deleteAction.bind(this, user)}>
          {app.translator.trans('core.forum.user_controls.delete_button')}
        </Button>
      );
    }

    return items;
  },

  /**
   * Delete the user.
   *
   * @param {import('../../common/models/User').default} user
   */
  deleteAction(user) {
    if (!confirm(app.translator.trans('core.forum.user_controls.delete_confirmation'))) {
      return;
    }

    user
      .delete()
      .then(() => {
        this.showDeletionAlert(user, 'success');
        if (app.current.matches(UserPage, { user })) {
          app.history.back();
        } else {
          window.location.reload();
        }
      })
      .catch(() => this.showDeletionAlert(user, 'error'));
  },

  /**
   * Show deletion alert of user.
   *
   * @param {import('../../common/models/User').default} user
   * @param {string} type
   */
  showDeletionAlert(user, type) {
    const message = {
      success: 'core.forum.user_controls.delete_success_message',
      error: 'core.forum.user_controls.delete_error_message',
    }[type];

    app.alerts.show(
      { type },
      app.translator.trans(message, {
        user,
        email: user.email(),
      })
    );
  },

  /**
   * Edit the user.
   *
   * @param {import('../../common/models/User').default} user
   */
  editAction(user) {
    app.modal.show(EditUserModal, { user });
  },
};
