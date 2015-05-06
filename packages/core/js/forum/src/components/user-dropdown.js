import Component from 'flarum/component';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';
import DropdownButton from 'flarum/components/dropdown-button';
import ActionButton from 'flarum/components/action-button';
import ItemList from 'flarum/utils/item-list';
import Separator from 'flarum/components/separator';

export default class UserDropdown extends Component {
  view() {
    var user = this.props.user;

    return DropdownButton.component({
      buttonClass: 'btn btn-default btn-naked btn-rounded btn-user',
      menuClass: 'pull-right',
      buttonContent: [avatar(user), ' ', m('span.label', username(user))],
      items: this.items().toArray()
    });
  }

  items() {
    var items = new ItemList();
    var user = this.props.user;

    items.add('profile',
      ActionButton.component({
        icon: 'user',
        label: 'Profile',
        href: app.route('user', {username: user.username()}),
        config: m.route
      })
    );

    items.add('settings',
      ActionButton.component({
        icon: 'cog',
        label: 'Settings',
        href: app.route('settings'),
        config: m.route
      })
    );

    if (user.groups().some((group) => group.id() == 1)) {
      items.add('administration',
        ActionButton.component({
          icon: 'wrench',
          label: 'Administration',
          href: app.config['base_url']+'/admin'
        })
      );
    }

    items.add('separator', Separator.component());

    items.add('logOut',
      ActionButton.component({
        icon: 'sign-out',
        label: 'Log Out',
        onclick: app.session.logout.bind(app.session)
      })
    );

    return items;
  }
}
