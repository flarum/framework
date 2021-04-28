import Component from '../../common/Component';
import Button from '../../common/components/Button';
import SelectDropdown from '../../common/components/SelectDropdown';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';
import Search from '../components/Search';

/**
 * The `HeaderSecondary` component displays secondary header controls, such as
 * the search box and the user menu. On the default skin, these are shown on the
 * right side of the header.
 */
export default class HeaderSecondary extends Component {
  view() {
    return <ul className="Header-controls">{listItems(this.items().toArray())}</ul>;
  }

  /**
   * Build an item list for the controls.
   *
   * @return {ItemList}
   */
  items() {
    const items = new ItemList();

    const LogInModal = () => import(/* webpackChunkName: "forum/components/LogInModal" */ './LogInModal');
    const SignUpModal = () => import(/* webpackChunkName: "forum/components/SignUpModal" */ './SignUpModal');

    items.add('search', Search.component({state: app.search}), 30);

    if (app.forum.attribute('showLanguageSelector') && Object.keys(app.data.locales).length > 1) {
      const locales = [];

      for (const locale in app.data.locales) {
        locales.push(
          Button.component(
            {
              active: app.data.locale === locale,
              icon: app.data.locale === locale ? 'fas fa-check' : true,
              onclick: () => {
                if (app.session.user) {
                  app.session.user.savePreferences({locale}).then(() => window.location.reload());
                } else {
                  document.cookie = `locale=${locale}; path=/; expires=Tue, 19 Jan 2038 03:14:07 GMT`;
                  window.location.reload();
                }
              },
            },
            app.data.locales[locale]
          )
        );
      }

      items.add(
        'locale',
        SelectDropdown.component(
          {
            buttonClassName: 'Button Button--link',
            accessibleToggleLabel: app.translator.trans('core.forum.header.locale_dropdown_accessible_label'),
          },
          locales
        ),
        20
      );
    }

    if (app.session.user) {
      if (!this.NotificationsDropdown) {
        import(/* webpackChunkName: "forum/components/NotificationsDropdown" */ './NotificationsDropdown').then(NotificationsDropdown => {
          this.NotificationsDropdown = NotificationsDropdown.default;
          m.redraw();
        });
      } else {
        items.add('notifications', this.NotificationsDropdown.component({state: app.notifications}), 10);
      }

      if (!this.SessionDropdown) {
        import(/* webpackChunkName: "forum/components/SessionDropdown" */ './SessionDropdown').then(SessionDropdown => {
          this.SessionDropdown = SessionDropdown.default;
          m.redraw();
        });
      } else {
        items.add('session', this.SessionDropdown.component(), 0);
      }

    } else {
      if (app.forum.attribute('allowSignUp')) {
        items.add(
          'signUp',
          Button.component(
            {
              className: 'Button Button--link',
              onclick: () => app.modal.show(SignUpModal),
            },
            app.translator.trans('core.forum.header.sign_up_link')
          ),
          10
        );
      }

      items.add(
        'logIn',
        Button.component(
          {
            className: 'Button Button--link',
            onclick: () => app.modal.show(LogInModal),
          },
          app.translator.trans('core.forum.header.log_in_link')
        ),
        0
      );
    }

    return items;
  }
}
