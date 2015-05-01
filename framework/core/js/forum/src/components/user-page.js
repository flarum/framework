import Component from 'flarum/component';
import ItemList from 'flarum/utils/item-list';
import IndexPage from 'flarum/components/index-page';
import DiscussionList from 'flarum/components/discussion-list';
import StreamContent from 'flarum/components/stream-content';
import StreamScrubber from 'flarum/components/stream-scrubber';
import UserCard from 'flarum/components/user-card';
import ComposerReply from 'flarum/components/composer-reply';
import ActionButton from 'flarum/components/action-button';
import LoadingIndicator from 'flarum/components/loading-indicator';
import DropdownSplit from 'flarum/components/dropdown-split';
import DropdownSelect from 'flarum/components/dropdown-select';
import NavItem from 'flarum/components/nav-item';
import Separator from 'flarum/components/separator';
import listItems from 'flarum/helpers/list-items';

export default class UserPage extends Component {
  /**

   */
  constructor(props) {
    super(props);

    app.history.push('user');
    app.current = this;
  }

  /*

   */
  setupUser(user) {
    this.user(user);
  }

  onload(element, isInitialized, context) {
    if (isInitialized) { return; }

    $('body').addClass('user-page');
    context.onunload = function() {
      $('body').removeClass('user-page');
    }
  }

  /**

   */
  view() {
    var user = this.user();

    return m('div', {config: this.onload.bind(this)}, user ? [
      UserCard.component({user, className: 'hero user-hero', editable: true, controlsButtonClass: 'btn btn-default'}),
      m('div.container', [
        m('nav.side-nav.user-nav', {config: this.affixSidebar}, [
          m('ul', listItems(this.sidebarItems().toArray()))
        ]),
        m('div.offset-content.user-content', this.content())
      ])
    ] : LoadingIndicator.component({className: 'loading-indicator-block'}));
  }

  /**

   */
  sidebarItems() {
    var items = new ItemList();

    items.add('nav',
      DropdownSelect.component({
        items: this.navItems().toArray(),
        wrapperClass: 'title-control'
      })
    );

    return items;
  }

  /**
    Build an item list for the navigation in the sidebar of the index page. By
    default this is just the 'All Discussions' link.

    @return {ItemList}
   */
  navItems() {
    var items = new ItemList();
    var user = this.user();

    items.add('activity',
      NavItem.component({
        href: app.route('user.activity', {username: user.username()}),
        label: 'Activity',
        icon: 'user'
      })
    );

    items.add('discussions',
      NavItem.component({
        href: app.route('user.discussions', {username: user.username()}),
        label: 'Discussions',
        icon: 'reorder',
        badge: user.discussionsCount()
      })
    );

    items.add('posts',
      NavItem.component({
        href: app.route('user.posts', {username: user.username()}),
        label: 'Posts',
        icon: 'comment-o',
        badge: user.commentsCount()
      })
    );

    if (app.session.user() === user) {
      items.add('separator', Separator.component());
      items.add('settings',
        NavItem.component({
          href: app.route('settings'),
          label: 'Settings',
          icon: 'cog'
        })
      );
    }

    return items;
  }

  /**
    Setup the sidebar DOM element to be affixed to the top of the viewport
    using Bootstrap's affix plugin.

    @param {DOMElement} element
    @param {Boolean} isInitialized
    @return {void}
   */
  affixSidebar(element, isInitialized, context) {
    if (isInitialized) { return; }

    var $sidebar = $(element);
    console.log($sidebar.find('> ul'), $sidebar.find('> ul').data('bs.affix'));
    $sidebar.find('> ul').affix({
      offset: {
        top: $sidebar.offset().top - $('.global-header').outerHeight(true) - parseInt($sidebar.css('margin-top')),
        bottom: $('.global-footer').outerHeight(true)
      }
    });
  }
}
