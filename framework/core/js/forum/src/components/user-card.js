import Component from 'flarum/component';
import humanTime from 'flarum/utils/human-time';
import ItemList from 'flarum/utils/item-list';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';
import icon from 'flarum/helpers/icon';
import DropdownButton from 'flarum/components/dropdown-button';
import ActionButton from 'flarum/components/action-button';
import UserBio from 'flarum/components/user-bio';
import AvatarEditor from 'flarum/components/avatar-editor';
import listItems from 'flarum/helpers/list-items';

export default class UserCard extends Component {
  view() {
    var user = this.props.user;
    var controls = this.controlItems().toArray();

    return m('div.user-card', {className: this.props.className, style: 'background-color: '+user.color()}, [
      m('div.darken-overlay'),
      m('div.container', [
        controls.length ? DropdownButton.component({
          items: controls,
          className: 'contextual-controls',
          menuClass: 'pull-right',
          buttonClass: this.props.controlsButtonClass
        }) : '',
        m('div.user-profile', [
          m('h2.user-identity', this.props.editable
            ? [AvatarEditor.component({user, className: 'user-avatar'}), username(user)]
            : m('a', {href: app.route('user', user), config: m.route}, [
              avatar(user, {className: 'user-avatar'}),
              username(user)
            ])
          ),
          m('ul.user-badges.badges', listItems(user.badges().toArray())),
          m('ul.user-info', listItems(this.infoItems().toArray()))
        ])
      ])
    ]);
  }

  controlItems() {
    var items = new ItemList();

    items.add('edit', ActionButton.component({ icon: 'pencil', label: 'Edit' }));
    items.add('delete', ActionButton.component({ icon: 'times', label: 'Delete' }));

    return items;
  }

  infoItems() {
    var items = new ItemList();
    var user = this.props.user;
    var online = user.online();

    items.add('bio',
      UserBio.component({
        user,
        editable: this.props.editable,
        wrapperClass: 'block-item'
      })
    );

    if (user.lastSeenTime()) {
      items.add('lastSeen',
        m('span.user-last-seen', {className: online ? 'online' : ''}, online
          ? [icon('circle'), ' Online']
          : [icon('clock-o'), ' ', humanTime(user.lastSeenTime())])
      );
    }

    items.add('joined', ['Joined ', humanTime(user.joinTime())]);

    return items;
  }
}
