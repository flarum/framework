import Component from 'flarum/component';
import ItemList from 'flarum/utils/item-list';
import listItems from 'flarum/helpers/list-items';
import Discussion from 'flarum/models/discussion';
import mixin from 'flarum/utils/mixin';

import DiscussionList from 'flarum/components/discussion-list';
import WelcomeHero from 'flarum/components/welcome-hero';
import DiscussionComposer from 'flarum/components/discussion-composer';
import LoginModal from 'flarum/components/login-modal';
import DiscussionPage from 'flarum/components/discussion-page';

import SelectInput from 'flarum/components/select-input';
import ActionButton from 'flarum/components/action-button';
import IndexNavItem from 'flarum/components/index-nav-item';
import LoadingIndicator from 'flarum/components/loading-indicator';
import DropdownSelect from 'flarum/components/dropdown-select';

export default class IndexPage extends Component {
  /**
   * @param {Object} props
   */
  constructor(props) {
    super(props);

    // If the user is returning from a discussion page, then take note of which
    // discussion they have just visited. After the view is rendered, we will
    // scroll down so that this discussion is in view.
    if (app.current instanceof DiscussionPage) {
      this.lastDiscussion = app.current.discussion();
    }

    var params = this.params();

    if (app.cache.discussionList) {
      // The discussion list component is stored in the app's cache so that it
      // can persist across interfaces. Since we will soon be redrawing the
      // discussion list from scratch, we need to invalidate the component's
      // subtree cache to ensure that it re-constructs the view.
      app.cache.discussionList.willBeRedrawn();

      // Compare the requested parameters (sort, search query) to the ones that
      // are currently present in the cached discussion list. If they differ, we
      // will clear the cache and set up a new discussion list component with
      // the new parameters.
      Object.keys(params).some(key => {
        if (app.cache.discussionList.props.params[key] !== params[key]) {
          app.cache.discussionList = null;
          return true;
        }
      });
    }

    if (!app.cache.discussionList) {
      app.cache.discussionList = new DiscussionList({ params });
    }

    app.history.push('index');
    app.current = this;
  }

  /**
   * Render the component.
   *
   * @return {Object}
   */
  view() {
    return m('div.index-area', {config: this.onload.bind(this)}, [
      this.hero(),
      m('div.container', [
        m('nav.side-nav.index-nav', {config: this.affixSidebar}, [
          m('ul', listItems(this.sidebarItems().toArray()))
        ]),
        m('div.offset-content.index-results', [
          m('div.index-toolbar', [
            m('ul.index-toolbar-view', listItems(this.viewItems().toArray())),
            m('ul.index-toolbar-action', listItems(this.actionItems().toArray()))
          ]),
          app.cache.discussionList.view()
        ])
      ])
    ]);
  }

  /**
   * Get the component to display as the hero.
   *
   * @return {Object}
   */
  hero() {
    return WelcomeHero.component();
  }

  /**
   * Build an item list for the sidebar of the index page. By default this is a
   * "New Discussion" button, and then a DropdownSelect component containing a
   * list of navigation items (see this.navItems).
   *
   * @return {ItemList}
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
   * Build an item list for the navigation in the sidebar of the index page. By
   * default this is just the 'All Discussions' link.
   *
   * @return {ItemList}
   */
  navItems() {
    var items = new ItemList();
    var params = this.stickyParams();

    items.add('allDiscussions',
      IndexNavItem.component({
        href: app.route('index', params),
        label: 'All Discussions',
        icon: 'comments-o'
      })
    );

    return items;
  }

  /**
   * Build an item list for the part of the toolbar which is concerned with how
   * the results are displayed. By default this is just a select box to change
   * the way discussions are sorted.
   *
   * @return {ItemList}
   */
  viewItems() {
    var items = new ItemList();

    var sortOptions = {};
    for (var i in app.cache.discussionList.sortMap()) {
      sortOptions[i] = i.substr(0, 1).toUpperCase()+i.substr(1);
    }

    items.add('sort',
      SelectInput.component({
        options: sortOptions,
        value: this.params().sort,
        onchange: this.reorder.bind(this)
      })
    );

    return items;
  }

  /**
   * Build an item list for the part of the toolbar which is about taking action
   * on the results. By default this is just a "mark all as read" button.
   *
   * @return {ItemList}
   */
  actionItems() {
    var items = new ItemList();

    if (app.session.user()) {
      items.add('markAllAsRead',
        ActionButton.component({
          title: 'Mark All as Read',
          icon: 'check',
          className: 'control-markAllAsRead btn btn-default btn-icon',
          onclick: this.markAllAsRead.bind(this)
        })
      );
    }

    return items;
  }

  /**
   * Return the current search query, if any. This is implemented to activate
   * the search box in the header.
   *
   * @see module:flarum/components/search-box
   * @return {String}
   */
  searching() {
    return this.params().q;
  }

  /**
   * Redirect to the index page without a search filter. This is called when the
   * 'x' is clicked in the search box in the header.
   *
   * @see module:flarum/components/search-box
   * @return void
   */
  clearSearch() {
    var params = this.params();
    delete params.q;
    m.route(app.route('index', params));
  }

  /**
   * Redirect to
   * @param {[type]} sort [description]
   * @return {[type]}
   */
  reorder(sort) {
    var params = this.params();
    if (sort === Object.keys(app.cache.discussionList.sortMap())[0]) {
      delete params.sort;
    } else {
      params.sort = sort;
    }
    m.route(app.route(this.props.routeName, params));
  }

  /**
   * Get URL parameters that stick between filter changes.
   *
   * @return {Object}
   */
  stickyParams() {
    return {
      sort: m.route.param('sort'),
      q: m.route.param('q')
    }
  }

  /**
   * Get parameters to pass to the DiscussionList component.
   *
   * @return {Object}
   */
  params() {
    var params = this.stickyParams();

    params.filter = m.route.param('filter');

    return params;
  }

  /**
   * Initialize the DOM.
   *
   * @param {DOMElement} element
   * @param {Boolean} isInitialized
   * @param {Object} context
   * @return {void}
   */
  onload(element, isInitialized, context) {
    if (isInitialized) return;

    this.element(element);

    $('body').addClass('index-page');
    context.onunload = function() {
      $('body').removeClass('index-page');
      $('.global-page').css('min-height', '');
    };

    app.setTitle('');

    // Work out the difference between the height of this hero and that of the
    // previous hero. Maintain the same scroll position relative to the bottom
    // of the hero so that the 'fixed' sidebar doesn't jump around.
    var heroHeight = this.$('.hero').outerHeight();
    var scrollTop = app.cache.scrollTop;

    $('.global-page').css('min-height', $(window).height() + heroHeight);
    $(window).scrollTop(scrollTop - (app.cache.heroHeight - heroHeight));

    app.cache.heroHeight = heroHeight;

    // If we've just returned from a discussion page, then the constructor will
    // have set the `lastDiscussion` property. If this is the case, we want to
    // scroll down to that discussion so that it's in view.
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
  }

  /**
   * Mithril hook, called when the controller is destroyed. Save the scroll
   * position, and minimize the composer.
   *
   * @return void
   */
  onunload() {
    app.cache.scrollTop = $(window).scrollTop();
    app.composer.minimize();
  }

  /**
   * Setup the sidebar DOM element to be affixed to the top of the viewport
   * using Bootstrap's affix plugin.
   *
   * @param {DOMElement} element
   * @param {Boolean} isInitialized
   * @return {void}
   */
  affixSidebar(element, isInitialized) {
    if (isInitialized) { return; }
    var $sidebar = $(element);

    // Don't affix the sidebar if it is taller than the viewport (otherwise
    // there would be no way to scroll through its content).
    if ($sidebar.outerHeight(true) > $(window).height() - $('.global-header').outerHeight(true)) return;

    $sidebar.find('> ul').affix({
      offset: {
        top: () => $sidebar.offset().top - $('.global-header').outerHeight(true) - parseInt($sidebar.css('margin-top')),
        bottom: () => (this.bottom = $('.global-footer').outerHeight(true))
      }
    });
  }

  /**
   * Initialize the composer for a new discussion.
   *
   * @return {Promise}
   */
  newDiscussion() {
    var deferred = m.deferred();

    if (app.session.user()) {
      this.composeNewDiscussion(deferred);
    } else {
      app.modal.show(
        new LoginModal({ onlogin: this.composeNewDiscussion.bind(this, deferred) })
      );
    }

    return deferred.promise;
  }

  composeNewDiscussion(deferred) {
    // @todo check global permissions
    var component = new DiscussionComposer({ user: app.session.user() });
    app.composer.load(component);
    app.composer.show();
    deferred.resolve(component);

    return deferred.promise;
  }

  /**
   * Mark all discussions as read.
   *
   * @return void
   */
  markAllAsRead() {
    app.session.user().save({ readTime: new Date() });
  }
};
