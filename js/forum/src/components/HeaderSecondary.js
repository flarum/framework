import Component from 'flarum/Component';
import Button from 'flarum/components/Button';
import LogInModal from 'flarum/components/LogInModal';
import SignUpModal from 'flarum/components/SignUpModal';
import SessionDropdown from 'flarum/components/SessionDropdown';
import NotificationsDropdown from 'flarum/components/NotificationsDropdown';
import ItemList from 'flarum/utils/ItemList';
import listItems from 'flarum/helpers/listItems';

/**
 * The `HeaderSecondary` component displays secondary footer controls, such as
 * the search box and the user menu. On the default skin, these are shown on the
 * right side of the header.
 */
export default class HeaderSecondary extends Component {
  view() {
    return (
      <ul className="header-controls">
        {listItems(this.items().toArray())}
      </ul>
    );
  }

  /**
   * Build an item list for the controls.
   *
   * @return {ItemList}
   */
  items() {
    const items = new ItemList();

    items.add('search', app.search.render());

    if (app.session.user) {
      items.add('notifications', NotificationsDropdown.component());
      items.add('session', SessionDropdown.component());
    } else {
      items.add('signUp',
        Button.component({
          children: 'Sign Up',
          className: 'btn btn-link',
          onclick: () => app.modal.show(new SignUpModal())
        })
      );

      items.add('logIn',
        Button.component({
          children: 'Log In',
          className: 'btn btn-link',
          onclick: () => app.modal.show(new LogInModal())
        })
      );
    }

    return items;
  }
}
