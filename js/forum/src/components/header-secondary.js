import Component from 'flarum/component';
import ActionButton from 'flarum/components/action-button';
import LoginModal from 'flarum/components/login-modal';
import SignupModal from 'flarum/components/signup-modal';
import UserDropdown from 'flarum/components/user-dropdown';
import UserNotifications from 'flarum/components/user-notifications';

import ItemList from 'flarum/utils/item-list';
import listItems from 'flarum/helpers/list-items';

export default class HeaderSecondary extends Component {
  view() {
    return m('ul.header-controls', listItems(this.items().toArray()));
  }

  items() {
    var items = new ItemList();

    items.add('search', app.search.render());

    if (app.session.user()) {
      items.add('notifications', UserNotifications.component({ user: app.session.user() }))
      items.add('user', UserDropdown.component({ user: app.session.user() }));
    }

    else {
      items.add('signUp',
        ActionButton.component({
          label: 'Sign Up',
          className: 'btn btn-link',
          onclick: () => app.modal.show(new SignupModal())
        })
      );

      items.add('logIn',
        ActionButton.component({
          label: 'Log In',
          className: 'btn btn-link',
          onclick: () => app.modal.show(new LoginModal())
        })
      );
    }

    return items;
  }
}
