import Component from 'flarum/component';
import UserCard from 'flarum/components/user-card';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';
import listItems from 'flarum/helpers/list-items';

/**
  Component for the username/avatar in a post header.
 */
export default class PostHeaderUser extends Component {
  constructor(props) {
    super(props);

    this.showCard = m.prop(false);
  }

  view() {
    var post = this.props.post;
    var user = post.user();

    return m('div.post-user', {config: this.onload.bind(this)}, [
      m('h3',
        user ? [
          m('a', {href: app.route('user', {username: user.username()}), config: m.route}, [
            avatar(user),
            username(user)
          ]),
          m('ul.badges', listItems(user.badges().toArray()))
        ] : [
          avatar(),
          username()
        ]
      ),
      this.showCard() ? UserCard.component({user, className: 'user-card-popover fade', controlsButtonClass: 'btn btn-default btn-icon btn-sm btn-naked'}) : ''
    ]);
  }

  onload(element, isInitialized) {
    if (isInitialized) { return; }

    this.element(element);

    var component = this;
    var timeout;
    this.$().bind('mouseover', '> a, .user-card', function() {
      clearTimeout(timeout);
      timeout = setTimeout(function() {
        component.showCard(true);
        m.redraw();
        setTimeout(() => component.$('.user-card').addClass('in'));
      }, 250);
    }).bind('mouseout', '> a, .user-card', function() {
      clearTimeout(timeout);
      timeout = setTimeout(function() {
        component.$('.user-card').removeClass('in').one('transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd', function() {
          component.showCard(false);
        });
      }, 250);
    });
  }
}
