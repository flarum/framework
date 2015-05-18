import Component from 'flarum/component';
import ItemList from 'flarum/utils/item-list';
import listItems from 'flarum/helpers/list-items';
import Discussion from 'flarum/models/discussion';
import mixin from 'flarum/utils/mixin';

import DiscussionList from 'flarum/components/discussion-list';
import WelcomeHero from 'flarum/components/welcome-hero';
import ComposerDiscussion from 'flarum/components/composer-discussion';
import LoginModal from 'flarum/components/login-modal';
import DiscussionPage from 'flarum/components/discussion-page';

import SelectInput from 'flarum/components/select-input';
import ActionButton from 'flarum/components/action-button';
import NavItem from 'flarum/components/nav-item';
import LoadingIndicator from 'flarum/components/loading-indicator';
import DropdownSelect from 'flarum/components/dropdown-select';

export default class IndexPage extends Component {
  constructor(props) {
    super(props);

    var params = this.params();
    if (app.cache.discussionList) {
      app.cache.discussionList.subtrees.map(subtree => subtree.invalidate());
      Object.keys(params).some(key => {
        if (app.cache.discussionList.props.params[key] !== params[key]) {
          app.cache.discussionList = null;
          return true;
        }
      });
    }
    if (!app.cache.discussionList) {
      app.cache.discussionList = new DiscussionList({params});
    }

    if (app.current instanceof DiscussionPage) {
      this.lastDiscussion = app.current.discussion();
    }

    app.history.push('index');
    app.current = this;
  }

  onunload() {
    app.cache.scrollTop = $(window).scrollTop();
    app.composer.minimize();
  }

  /**
    Params that stick between filter changes
   */
  stickyParams() {
    return {
      sort: m.route.param('sort'),
      show: m.route.param('show'),
      q: m.route.param('q')
    }
  }

  /**
    Params which are passed to the DiscussionList
   */
  params() {
    var params = this.stickyParams();
    params.filter = m.route.param('filter');
    return params;
  }

  reorder(sort) {
    var params = this.params();
    if (sort === 'recent') {
      delete params.sort;
    } else {
      params.sort = sort;
    }
    m.route(app.route(this.props.routeName, params));
  }

  /**
    Render the component.

    @method view
    @return void
   */
  view() {
    var sortOptions = {};
    for (var i in app.cache.discussionList.sortMap()) {
      sortOptions[i] = i.substr(0, 1).toUpperCase()+i.substr(1);
    }

    return m('div.index-area', {config: this.onload.bind(this)}, [
      WelcomeHero.component(),
      m('div.container', [
        m('nav.side-nav.index-nav', {config: this.affixSidebar}, [
          m('ul', listItems(this.sidebarItems().toArray()))
        ]),
        m('div.offset-content.index-results', [
          m('div.index-toolbar', [
            m('div.index-toolbar-view', [
              SelectInput.component({
                options: sortOptions,
                value: m.route.param('sort'),
                onchange: this.reorder.bind(this)
              }),
            ]),
            m('div.index-toolbar-action', [
              ActionButton.component({
                title: 'Mark All as Read',
                icon: 'check',
                className: 'control-markAllAsRead btn btn-default btn-icon',
                onclick: this.markAllAsRead.bind(this)
              })
            ])
          ]),
          app.cache.discussionList.view()
        ])
      ])
    ])
  }

  onload(element, isInitialized, context) {
    if (isInitialized) { return; }

    this.element(element);

    $('body').addClass('index-page');
    context.onunload = function() {
      $('body').removeClass('index-page');
    }

    var scrollTop = app.cache.scrollTop;
    $(window).scrollTop(scrollTop);

    if (this.lastDiscussion) {
      var $discussion = this.$('.discussion-summary[data-id='+this.lastDiscussion.id()+']');
      if ($discussion.length) {
        var indexTop = $('#header').outerHeight();
        var discussionTop = $discussion.offset().top;
        if (discussionTop < scrollTop + indexTop || discussionTop + $discussion.outerHeight() > scrollTop + $(window).height()) {
          $(window).scrollTop(discussionTop - indexTop);
        }
      }
    }

    app.setTitle('');
  }

  newDiscussion() {
    if (app.session.user()) {
      app.composer.load(new ComposerDiscussion({ user: app.session.user() }));
      app.composer.show();
      return true;
    } else {
      app.modal.show(new LoginModal({
        message: 'You must be logged in to do that.',
        callback: this.newDiscussion.bind(this)
      }));
    }
  }

  markAllAsRead() {
    app.session.user().save({ readTime: new Date() });
  }

  /**
    Build an item list for the sidebar of the index page. By default this is a
    "New Discussion" button, and then a DropdownSelect component containing a
    list of navigation items (see this.navItems).

    @return {ItemList}
   */
  sidebarItems() {
    var items = new ItemList();

    items.add('newDiscussion',
      ActionButton.component({
        label: 'Start a Discussion',
        icon: 'edit',
        className: 'btn btn-primary new-discussion',
        wrapperClass: 'primary-control',
        onclick: this.newDiscussion.bind(this)
      })
    );

    items.add('nav',
      DropdownSelect.component({
        items: this.navItems(this).toArray(),
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
    var params = {sort: m.route.param('sort')};

    items.add('allDiscussions',
      NavItem.component({
        href: app.route('index', params),
        label: 'All Discussions',
        icon: 'comments-o'
      })
    );

    return items;
  }

  /**
    Setup the sidebar DOM element to be affixed to the top of the viewport
    using Bootstrap's affix plugin.

    @param {DOMElement} element
    @param {Boolean} isInitialized
    @return {void}
   */
  affixSidebar(element, isInitialized) {
    if (isInitialized) { return; }
    var $sidebar = $(element);
    $sidebar.find('> ul').affix({
      offset: {
        top: () => $sidebar.offset().top - $('.global-header').outerHeight(true) - parseInt($sidebar.css('margin-top')),
        bottom: () => (this.bottom = $('.global-footer').outerHeight(true))
      }
    });
  }
};
