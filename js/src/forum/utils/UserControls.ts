import Button from '../../common/components/Button';
import Separator from '../../common/components/Separator';
// import EditUserModal from '../components/EditUserModal';
import UserPage from '../components/UserPage';
import ItemList from '../../common/utils/ItemList';
import User from "../../common/models/User";

/**
 * The `UserControls` utility constructs a list of buttons for a user which
 * perform actions on it.
 */
export default {
  /**
   * Get a list of controls for a user.
   *
   * @param user
   * @param context The parent component under which the controls menu will
   *     be displayed.
   */
  controls(user: User, context: any): ItemList {
    const items = new ItemList();

    ['user', 'moderation', 'destructive'].forEach(section => {
      const controls = this[section + 'Controls'](user, context).toArray();
      if (controls.length) {
        controls.forEach(item => items.add(item.itemName, item));
        items.add(section + 'Separator', Separator.component());
      }
    });

    return items;
  },

  /**
   * Get controls for a user pertaining to the current user (e.g. poke, follow).
   */
  userControls(): ItemList {
    return new ItemList();
  },

  /**
   * Get controls for a user pertaining to moderation (e.g. suspend, edit).
   */
  moderationControls(user: User): ItemList {
    const items = new ItemList();

    if (user.canEdit()) {
      items.add('edit', Button.component({
        icon: 'fas fa-pencil-alt',
        children: app.translator.trans('core.forum.user_controls.edit_button'),
        onclick: this.editAction.bind(user)
      }));
    }

    return items;
  },

  /**
   * Get controls for a user which are destructive (e.g. delete).
   */
  destructiveControls(user: User): ItemList {
    const items = new ItemList();

    if (user.id() !== '1' && user.canDelete()) {
      items.add('delete', Button.component({
        icon: 'fas fa-times',
        children: app.translator.trans('core.forum.user_controls.delete_button'),
        onclick: this.deleteAction.bind(user)
      }));
    }

    return items;
  },

  /**
   * Delete the user.
   */
  deleteAction(this: User) {
    if (confirm(app.translator.transText('core.forum.user_controls.delete_confirmation'))) {
      this.delete().then(() => {
        if (app.current instanceof UserPage && app.current.user === this) {
          app.history.back();
        } else {
          window.location.reload();
        }
      });
    }
  },

  /**
   * Edit the user.
   */
  editAction(this: User) {
    app.modal.show(new EditUserModal({user: this}));
  }
};
