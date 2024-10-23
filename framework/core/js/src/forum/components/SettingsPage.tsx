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
import classList from '../../common/utils/classList';
import ThemeMode from '../../common/components/ThemeMode';
import { camelCaseToSnakeCase } from '../../common/utils/string';
import { ComponentAttrs } from '../../common/Component';

/**
 * The `SettingsPage` component displays the user's settings control panel, in
 * the context of their user profile.
 */
export default class SettingsPage<CustomAttrs extends IUserPageAttrs = IUserPageAttrs> extends UserPage<CustomAttrs> {
  discloseOnlineLoading?: boolean;
  colorSchemeLoading?: boolean;

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

  sectionProps(): Record<string, ComponentAttrs> {
    return {
      account: { className: 'FieldSet--col' },
      colorScheme: {
        className: 'FieldSet--col',
        visible: () => app.allowUserColorScheme,
      },
    };
  }

  /**
   * Build an item list for the user's settings controls.
   */
  settingsItems() {
    const items = new ItemList<Mithril.Children>();

    ['account', 'notifications', 'privacy', 'colorScheme'].forEach((section, index) => {
      const sectionItems = `${section}Items` as 'accountItems' | 'notificationsItems' | 'privacyItems';

      const { className, visible, ...props } = this.sectionProps()[section] || {};

      if (visible && visible() === false) return;

      items.add(
        section,
        <FieldSet
          className={classList(`Settings-${section} FieldSet--min`, className || '')}
          label={app.translator.trans(`core.forum.settings.${camelCaseToSnakeCase(section)}_heading`)}
          {...props}
        >
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

  /**
   * Color schemes.
   */
  colorSchemeItems() {
    const items = new ItemList<Mithril.Children>();

    ThemeMode.colorSchemes.forEach((mode) => {
      items.add(
        mode.id,
        <ThemeMode
          mode={mode.id}
          label={mode.label || app.translator.trans('core.forum.settings.color_schemes.' + mode.id.replace('-', '_') + '_mode_label')}
          selected={this.user!.preferences()?.colorScheme === mode.id}
          loading={this.colorSchemeLoading}
          onclick={() => {
            this.colorSchemeLoading = true;

            this.user!.savePreferences({ colorScheme: mode.id }).then(() => {
              this.colorSchemeLoading = false;
              app.setColorScheme(mode.id);
              m.redraw();
            });
          }}
        />,
        100
      );
    });

    return items;
  }
}
