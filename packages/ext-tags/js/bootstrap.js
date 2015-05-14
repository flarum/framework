import { extend, override } from 'flarum/extension-utils';
import Model from 'flarum/model';
import Discussion from 'flarum/models/discussion';
import IndexPage from 'flarum/components/index-page';
import DiscussionPage from 'flarum/components/discussion-page';
import DiscussionList from 'flarum/components/discussion-list';
import DiscussionHero from 'flarum/components/discussion-hero';
import Separator from 'flarum/components/separator';
import ActionButton from 'flarum/components/action-button';
import NavItem from 'flarum/components/nav-item';
import ComposerDiscussion from 'flarum/components/composer-discussion';
import SettingsPage from 'flarum/components/settings-page';
import ActivityPost from 'flarum/components/activity-post';
import icon from 'flarum/helpers/icon';
import app from 'flarum/app';

import Category from 'categories/models/category';
import CategoriesPage from 'categories/components/categories-page';
import CategoryHero from 'categories/components/category-hero';
import CategoryNavItem from 'categories/components/category-nav-item';
import MoveDiscussionModal from 'categories/components/move-discussion-modal';
import NotificationDiscussionMoved from 'categories/components/notification-discussion-moved';
import PostDiscussionMoved from 'categories/components/post-discussion-moved';
import categoryLabel from 'categories/helpers/category-label';
import categoryIcon from 'categories/helpers/category-icon';

app.initializers.add('categories', function() {
  // Register routes.
  app.routes['categories'] = ['/categories', CategoriesPage.component()];
  app.routes['category'] = ['/c/:categories', IndexPage.component()];

  // @todo support combination with filters
  // app.routes['category.filter'] = ['/c/:slug/:filter', IndexPage.component({category: true})];

  // Register models.
  app.store.models['categories'] = Category;
  Discussion.prototype.category = Model.one('category');

  // Register components.
  app.postComponentRegistry['discussionMoved'] = PostDiscussionMoved;
  app.notificationComponentRegistry['discussionMoved'] = NotificationDiscussionMoved;

  // ---------------------------------------------------------------------------
  // INDEX PAGE
  // ---------------------------------------------------------------------------

  // Add a category label to each discussion in the discussion list.
  extend(DiscussionList.prototype, 'infoItems', function(items, discussion) {
    var category = discussion.category();
    if (category && category.slug() !== this.props.params.categories) {
      items.add('category', categoryLabel(category), {first: true});
    }
  });

  // Add a link to the categories page, as well as a list of all the categories,
  // to the index page's sidebar.
  extend(IndexPage.prototype, 'navItems', function(items) {
    items.add('categories', NavItem.component({
      icon: 'reorder',
      label: 'Categories',
      href: app.route('categories'),
      config: m.route
    }), {last: true});

    items.add('separator', Separator.component(), {last: true});

    items.add('uncategorized', CategoryNavItem.component({params: this.stickyParams()}), {last: true});

    app.store.all('categories').forEach(category => {
      items.add('category'+category.id(), CategoryNavItem.component({category, params: this.stickyParams()}), {last: true});
    });
  });

  IndexPage.prototype.currentCategory = function() {
    var slug = this.params().categories;
    if (slug) {
      return app.store.getBy('categories', 'slug', slug);
    }
  };

  // If currently viewing a category, insert a category hero at the top of the
  // view.
  extend(IndexPage.prototype, 'view', function(view) {
    var category = this.currentCategory();
    if (category) {
      view.children[0] = CategoryHero.component({category});
    }
  });

  // If currently viewing a category, restyle the 'new discussion' button to use
  // the category's color.
  extend(IndexPage.prototype, 'sidebarItems', function(items) {
    var category = this.currentCategory();
    if (category) {
      items.newDiscussion.content.props.style = 'background-color: '+category.color();
    }
  });

  // Add a parameter for the IndexPage to pass on to the DiscussionList that
  // will let us filter discussions by category.
  extend(IndexPage.prototype, 'params', function(params) {
    params.categories = m.route.param('categories');
  });

  // Translate that parameter into a gambit appended to the search query.
  extend(DiscussionList.prototype, 'params', function(params) {
    if (params.categories) {
      params.q = (params.q || '')+' category:'+params.categories;
      delete params.categories;
    }
  });

  // ---------------------------------------------------------------------------
  // DISCUSSION PAGE
  // ---------------------------------------------------------------------------

  // Include a discussion's category when fetching it.
  extend(DiscussionPage.prototype, 'params', function(params) {
    params.include.push('category');
  });

  // Restyle a discussion's hero to use its category color.
  extend(DiscussionHero.prototype, 'view', function(view) {
    var category = this.props.discussion.category();
    if (category) {
      view.attrs.style = 'background-color: '+category.color();
    }
  });

  // Add the name of a discussion's category to the discussion hero, displayed
  // before the title. Put the title on its own line.
  extend(DiscussionHero.prototype, 'items', function(items) {
    var category = this.props.discussion.category();
    if (category) {
      items.add('category', categoryLabel(category, {inverted: true}), {before: 'title'});
      items.title.content.wrapperClass = 'block-item';
    }
  });

  // Add a control allowing the discussion to be moved to another category.
  extend(Discussion.prototype, 'controls', function(items) {
    if (this.canEdit()) {
      items.add('move', ActionButton.component({
        label: 'Move',
        icon: 'arrow-right',
        onclick: () => app.modal.show(new MoveDiscussionModal({discussion: this}))
      }), {after: 'rename'});
    }
  });

  // ---------------------------------------------------------------------------
  // COMPOSER
  // ---------------------------------------------------------------------------

  // When the 'new discussion' button is clicked...
  override(IndexPage.prototype, 'newDiscussion', function(original) {
    var slug = this.params().categories;

    // If we're currently viewing a specific category, or if the user isn't
    // logged in, then we'll let the core code proceed. If that results in the
    // composer appearing, we'll set the composer's current category to the one
    // we're viewing.
    if (slug || !app.session.user()) {
      if (original()) {
        var category = app.store.getBy('categories', 'slug', slug);
        app.composer.component.category(category);
      }
    } else {
      // If we're logged in and we're viewing All Discussions, we'll present the
      // user with a category selection dialog before proceeding to show the
      // composer.
      var modal = new MoveDiscussionModal({
        onchange: category => {
          original();
          app.composer.component.category(category);
        }
      });
      app.modal.show(modal);
    }
  });

  // Add category-selection abilities to the discussion composer.
  ComposerDiscussion.prototype.category = m.prop();
  ComposerDiscussion.prototype.chooseCategory = function() {
    var modal = new MoveDiscussionModal({
      onchange: category => {
        this.category(category);
        this.$('textarea').focus();
      }
    });
    app.modal.show(modal);
  };

  // Add a category-selection menu to the discussion composer's header, after
  // the title.
  extend(ComposerDiscussion.prototype, 'headerItems', function(items) {
    var category = this.category();

    items.add('category', m('a[href=javascript:;][tabindex=-1].btn.btn-link.control-change-category', {onclick: this.chooseCategory.bind(this)}, [
      categoryIcon(category), ' ',
      m('span.label', category ? category.title() : 'Uncategorized'),
      icon('sort')
    ]));
  });

  // Add the selected category as data to submit to the server.
  extend(ComposerDiscussion.prototype, 'data', function(data) {
    data.links = data.links || {};
    data.links.category = this.category();
  });

  // ---------------------------------------------------------------------------
  // USER PROFILE
  // ---------------------------------------------------------------------------

  // Add a category label next to the discussion title in post activity items.
  extend(ActivityPost.prototype, 'headerItems', function(items) {
    var category = this.props.activity.post().discussion().category();
    if (category) {
      items.add('category', categoryLabel(category));
    }
  });

  // Add a notification preference.
  extend(SettingsPage.prototype, 'notificationTypes', function(items) {
    items.add('discussionMoved', {
      name: 'discussionMoved',
      label: [icon('arrow-right'), ' Someone moves a discussion I started']
    });
  });
});
