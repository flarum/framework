import app from '../../forum/app';
import UserPage, { IUserPageAttrs } from './UserPage';
import ItemList from '../../common/utils/ItemList';
import Switch from '../../common/components/Switch';
import Button from '../../common/components/Button';
import FieldSet from '../../common/components/FieldSet';
import NotificationGrid from './NotificationGrid';
import ChangePasswordModal from './ChangePasswordModal';
import ChangeEmailModal from './ChangeEmailModal';
import listItems from '../../common/helpers/listItems';
import extractText from '../../common/utils/extractText';
import type Mithril from 'mithril';

/**
 * The `SettingsPage` component displays the user's settings control panel, in
 * the context of their user profile.
 */
export default class SettingsPage<CustomAttrs extends IUserPageAttrs = IUserPageAttrs> extends UserPage<CustomAttrs> {
  discloseOnlineLoading?: boolean;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.show(app.session.user!);

    app.setTitle(extractText(app.translator.trans('core.forum.settings.title')));
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
   */
  settingsItems() {
    const items = new ItemList<Mithril.Children>();

    ['account', 'notifications', 'privacy'].forEach((section, index) => {
      const sectionItems = `${section}Items` as 'accountItems' | 'notificationsItems' | 'privacyItems';

      items.add(
        section,
        <FieldSet className={`Settings-${section}`} label={app.translator.trans(`core.forum.settings.${section}_heading`)}>
          {this[sectionItems]().toArray()}
        </FieldSet>,
        100 - index * 10
      );
    });

    return items;
  }

  /**
   * Build an item list for the user's account settings.
   */
  accountItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'changePassword',
      <Button className="Button" onclick={() => app.modal.show(ChangePasswordModal)}>
        {app.translator.trans('core.forum.settings.change_password_button')}
      </Button>,
      100
    );

    items.add(
      'changeEmail',
      <Button className="Button" onclick={() => app.modal.show(ChangeEmailModal)}>
        {app.translator.trans('core.forum.settings.change_email_button')}
      </Button>,
      90
    );

    return items;
  }

  /**
   * Build an item list for the user's notification settings.
   */
  notificationsItems() {
    const items = new ItemList<Mithril.Children>();

    items.add('notificationGrid', <NotificationGrid user={this.user} />, 100);

    return items;
  }

  /**
   * Build an item list for the user's privacy settings.
   */
  privacyItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'discloseOnline',
      <Switch
        state={this.user!.preferences()?.discloseOnline}
        onchange={(value: boolean) => {
          this.discloseOnlineLoading = true;

          this.user!.savePreferences({ discloseOnline: value }).then(() => {
            this.discloseOnlineLoading = false;
            m.redraw();
          });
        }}
        loading={this.discloseOnlineLoading}
      >
        {app.translator.trans('core.forum.settings.privacy_disclose_online_label')}
      </Switch>,
      100
    );

    return items;
  }
}
