import Button from 'flarum/components/Button';
import Separator from 'flarum/components/Separator';
import ItemList from 'flarum/utils/ItemList';

/**
 * The `UserControls` utility constructs a list of buttons for a user which
 * perform actions on it.
 */
export default {
  /**
   * Get a list of controls for a user.
   *
   * @param {User} user
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @public
   */
  controls(discussion, context) {
    const items = new ItemList();

    ['user', 'moderation', 'destructive'].forEach(section => {
      const controls = this[section + 'Controls'](discussion, context).toArray();
      if (controls.length) {
        items.add(section, controls);
        items.add(section + 'Separator', Separator.component());
      }
    });

    return items;
  },

  /**
   * Get controls for a user pertaining to the current user (e.g. poke, follow).
   *
   * @param {User} user
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  userControls() {
    return new ItemList();
  },

  /**
   * Get controls for a user pertaining to moderation (e.g. suspend, edit).
   *
   * @param {User} user
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  moderationControls(user) {
    const items = new ItemList();

    if (user.canEdit()) {
      items.add('edit', Button.component({
        icon: 'pencil',
        children: 'Edit',
        onclick: this.editAction.bind(user)
      }));
    }

    return items;
  },

  /**
   * Get controls for a user which are destructive (e.g. delete).
   *
   * @param {User} user
   * @param {*} context The parent component under which the controls menu will
   *     be displayed.
   * @return {ItemList}
   * @protected
   */
  destructiveControls(user) {
    const items = new ItemList();

    if (user.canDelete()) {
      items.add('delete', Button.component({
        icon: 'times',
        children: 'Delete',
        onclick: this.deleteAction.bind(user)
      }));
    }

    return items;
  },

  /**
   * Delete the user.
   */
  deleteAction() {
    // TODO
  },

  /**
   * Edit the user.
   */
  editAction() {
    // TODO
  }
};
