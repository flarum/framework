import UserPage from 'flarum/components/user-page';
import ItemList from 'flarum/utils/item-list';
import SwitchInput from 'flarum/components/switch-input';
import ActionButton from 'flarum/components/action-button';
import FieldSet from 'flarum/components/field-set';
import NotificationGrid from 'flarum/components/notification-grid';
import ChangePasswordModal from 'flarum/components/change-password-modal';
import ChangeEmailModal from 'flarum/components/change-email-modal';
import DeleteAccountModal from 'flarum/components/delete-account-modal';
import listItems from 'flarum/helpers/list-items';
import icon from 'flarum/helpers/icon';

export default class SettingsPage extends UserPage {
  /**

   */
  constructor(props) {
    super(props);

    this.setupUser(app.session.user());
    app.setTitle('Settings');
  }

  content() {
    return m('div.settings', [
      m('ul', listItems(this.settingsItems().toArray()))
    ]);
  }

  settingsItems() {
    var items = new ItemList();

    items.add('account',
      FieldSet.component({
        label: 'Account',
        className: 'settings-account',
        fields: this.accountItems().toArray()
      })
    );

    items.add('notifications',
      FieldSet.component({
        label: 'Notifications',
        className: 'settings-account',
        fields: [NotificationGrid.component({
          types: this.notificationTypes().toArray(),
          user: this.user()
        })]
      })
    );

    items.add('privacy',
      FieldSet.component({
        label: 'Privacy',
        fields: this.privacyItems().toArray()
      })
    );

    return items;
  }

  accountItems() {
    var items = new ItemList();

    items.add('changePassword',
      ActionButton.component({
        label: 'Change Password',
        className: 'btn btn-default',
        onclick: () => app.modal.show(new ChangePasswordModal())
      })
    );

    items.add('changeEmail',
      ActionButton.component({
        label: 'Change Email',
        className: 'btn btn-default',
        onclick: () => app.modal.show(new ChangeEmailModal())
      })
    );

    items.add('deleteAccount',
      ActionButton.component({
        label: 'Delete Account',
        className: 'btn btn-default btn-danger',
        onclick: () => app.modal.show(new DeleteAccountModal())
      })
    );

    return items;
  }

  save(key) {
    return (value, control) => {
      var preferences = this.user().preferences();
      preferences[key] = value;

      control.loading(true);
      m.redraw();

      this.user().save({preferences}).then(() => {
        control.loading(false);
        m.redraw();
      });
    };
  }

  privacyItems() {
    var items = new ItemList();

    items.add('discloseOnline',
      SwitchInput.component({
        label: 'Allow others to see when I am online',
        state: this.user().preferences().discloseOnline,
        onchange: (value, component) => {
          this.user().pushData({lastSeenTime: null});
          this.save('discloseOnline')(value, component);
        }
      })
    );

    return items;
  }

  notificationTypes() {
    var items = new ItemList();

    items.add('discussionRenamed', {
      name: 'discussionRenamed',
      label: [icon('pencil'), ' Someone renames a discussion I started']
    });

    return items;
  }
}
