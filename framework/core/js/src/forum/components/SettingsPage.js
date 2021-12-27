import app from '../../forum/app';
import UserPage from './UserPage';
import ItemList from '../../common/utils/ItemList';
import Switch from '../../common/components/Switch';
import Button from '../../common/components/Button';
import FieldSet from '../../common/components/FieldSet';
import NotificationGrid from './NotificationGrid';
import ChangePasswordModal from './ChangePasswordModal';
import ChangeEmailModal from './ChangeEmailModal';
import listItems from '../../common/helpers/listItems';

/**
 * The `SettingsPage` component displays the user's settings control panel, in
 * the context of their user profile.
 */
export default class SettingsPage extends UserPage {
  oninit(vnode) {
    super.oninit(vnode);

    this.show(app.session.user);

    app.setTitle(app.translator.trans('core.forum.settings.title'));
  }

  content() {
    return (
      <div className="SettingsPage">
        <ul>{listItems(this.settingsItems().toArray())}</ul>
      </div>
    );
  }

  /**
   * Build an item list for the user's settings controls.
   *
   * @return {ItemList<import('mithril').Children>}
   */
  settingsItems() {
    const items = new ItemList();

    ['account', 'notifications', 'privacy'].forEach((section) => {
      items.add(
        section,
        <FieldSet className={`Settings-${section}`} label={app.translator.trans(`core.forum.settings.${section}_heading`)}>
          {this[`${section}Items`]().toArray()}
        </FieldSet>
      );
    });

    return items;
  }

  /**
   * Build an item list for the user's account settings.
   *
   * @return {ItemList<import('mithril').Children>}
   */
  accountItems() {
    const items = new ItemList();

    items.add(
      'changePassword',
      <Button className="Button" onclick={() => app.modal.show(ChangePasswordModal)}>
        {app.translator.trans('core.forum.settings.change_password_button')}
      </Button>
    );

    items.add(
      'changeEmail',
      <Button className="Button" onclick={() => app.modal.show(ChangeEmailModal)}>
        {app.translator.trans('core.forum.settings.change_email_button')}
      </Button>
    );

    return items;
  }

  /**
   * Build an item list for the user's notification settings.
   *
   * @return {ItemList<import('mithril').Children>}
   */
  notificationsItems() {
    const items = new ItemList();

    items.add('notificationGrid', <NotificationGrid user={this.user} />);

    return items;
  }

  /**
   * Build an item list for the user's privacy settings.
   *
   * @return {ItemList<import('mithril').Children>}
   */
  privacyItems() {
    const items = new ItemList();

    items.add(
      'discloseOnline',
      <Switch
        state={this.user.preferences().discloseOnline}
        onchange={(value) => {
          this.discloseOnlineLoading = true;

          this.user.savePreferences({ discloseOnline: value }).then(() => {
            this.discloseOnlineLoading = false;
            m.redraw();
          });
        }}
        loading={this.discloseOnlineLoading}
      >
        {app.translator.trans('core.forum.settings.privacy_disclose_online_label')}
      </Switch>
    );

    return items;
  }
}
