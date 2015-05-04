import { extend, override } from 'flarum/extension-utils';
import Model from 'flarum/model';
import Component from 'flarum/component';
import Discussion from 'flarum/models/discussion';
import IndexPage from 'flarum/components/index-page';
import DiscussionPage from 'flarum/components/discussion-page';
import DiscussionList from 'flarum/components/discussion-list';
import DiscussionHero from 'flarum/components/discussion-hero';
import Separator from 'flarum/components/separator';
import NavItem from 'flarum/components/nav-item';
import ActionButton from 'flarum/components/action-button';
import ComposerDiscussion from 'flarum/components/composer-discussion';
import ActivityPost from 'flarum/components/activity-post';
import icon from 'flarum/helpers/icon';

import CategoriesPage from 'categories/components/categories-page';
import Category from 'categories/category';
import PostDiscussionMoved from 'categories/components/post-discussion-moved';

import app from 'flarum/app';

Discussion.prototype.category = Model.one('category');

app.initializers.add('categories', function() {
  app.routes['categories'] = ['/categories', CategoriesPage.component()];

  app.routes['category'] = ['/c/:categories', IndexPage.component({category: true})];


  // @todo support combination with filters
  // app.routes['category.filter'] = ['/c/:slug/:filter', IndexPage.component({category: true})];

  app.postComponentRegistry['discussionMoved'] = PostDiscussionMoved;
  app.store.model('categories', Category);

  extend(DiscussionList.prototype, 'infoItems', function(items, discussion) {
    var category = discussion.category();
    if (category && category.slug() !== this.props.params.categories) {
      items.add('category', m('span.category', {style: 'color: '+category.color()}, category.title()), {first: true});
    }

    return items;
  });

  class CategoryNavItem extends NavItem {
    view() {
      var category = this.props.category;
      var active = this.constructor.active(this.props);
      return m('li'+(active ? '.active' : ''), m('a', {href: this.props.href, config: m.route, onclick: () => {app.cache.discussionList = null; m.redraw.strategy('none')}, style: active ? 'color: '+category.color() : ''}, [
        m('span.icon.category-icon', {style: 'background-color: '+category.color()}),
        category.title()
      ]));
    }

    static props(props) {
      var category = props.category;
      props.params.categories = category.slug();
      props.href = app.route('category', props.params);
      props.label = category.title();

      return props;
    }
  }

  extend(IndexPage.prototype, 'navItems', function(items) {
    items.add('categories', NavItem.component({
      icon: 'reorder',
      label: 'Categories',
      href: app.route('categories'),
      config: m.route
    }), {last: true});

    items.add('separator', Separator.component(), {last: true});

    app.store.all('categories').forEach(category => {
      items.add('category'+category.id(), CategoryNavItem.component({category, params: this.stickyParams()}), {last: true});
    });

    return items;
  });

  extend(IndexPage.prototype, 'params', function(params) {
    params.categories = this.props.category ? m.route.param('categories') : undefined;
    return params;
  });

  class CategoryHero extends Component {
    view() {
      var category = this.props.category;

      return m('header.hero.category-hero', {style: 'background-color: '+category.color()}, [
        m('div.container', [
          m('div.container-narrow', [
            m('h2', category.title()),
            m('div.subtitle', category.description())
          ])
        ])
      ]);
    }
  }

  extend(IndexPage.prototype, 'view', function(view) {
    if (this.props.category) {
      var slug = this.params().categories;
      var category = app.store.all('categories').filter(category => category.slug() == slug)[0];
      view.children[0] = CategoryHero.component({category});
    }
    return view;
  });

  extend(IndexPage.prototype, 'sidebarItems', function(items) {
    var slug = this.params().categories;
    var category = app.store.all('categories').filter(category => category.slug() == slug)[0];
    if (category) {
      items.newDiscussion.content.props.style = 'background-color: '+category.color();
    }
    return items;
  });

  extend(DiscussionList.prototype, 'params', function(params) {
    if (params.categories) {
      params.q = (params.q || '')+' category:'+params.categories;
      delete params.categories;
    }
    return params;
  });

  extend(DiscussionPage.prototype, 'params', function(params) {
    params.include += ',category';
    return params;
  });

  extend(DiscussionHero.prototype, 'view', function(view) {
    var category = this.props.discussion.category();
    if (category) {
      view.attrs.style = 'background-color: '+category.color();
    }
    return view;
  });

  extend(DiscussionHero.prototype, 'items', function(items) {
    var category = this.props.discussion.category();
    if (category) {
      items.add('category', m('span.category', category.title()), {before: 'title'});
      items.title.content.wrapperClass = 'block-item';
    }
    return items;
  });

  class MoveDiscussionModal extends Component {
    constructor(props) {
      super(props);

      this.categories = m.prop(app.store.all('categories'));
    }

    view() {
      var discussion = this.props.discussion;

      return m('div.modal-dialog.modal-move-discussion', [
        m('div.modal-content', [
          m('button.btn.btn-icon.btn-link.close.back-control', {onclick: app.modal.close.bind(app.modal)}, icon('times')),
          m('div.modal-header', m('h3.title-control', discussion
            ? ['Move ', m('em', discussion.title()), ' from ', m('span.category', {style: 'color: '+discussion.category().color()}, discussion.category().title()), ' to...']
            : ['Start a Discussion In...'])),
          m('div', [
            m('ul.category-list', [
              this.categories().map(category =>
                (discussion && category.id() === discussion.category().id()) ? '' : m('li.category-tile', {style: 'background-color: '+category.color()}, [
                  m('a[href=javascript:;]', {onclick: this.save.bind(this, category)}, [
                    m('h3.title', category.title()),
                    m('p.description', category.description()),
                    m('span.count', category.discussionsCount()+' discussions'),
                  ])
                ])
              )
            ])
          ])
        ])
      ]);
    }

    save(category) {
      var discussion = this.props.discussion;

      if (discussion) {
        discussion.save({links: {category}}).then(discussion => {
          if (app.current instanceof DiscussionPage) {
            app.current.stream().sync();
          }
          m.redraw();
        });
      }

      this.props.onchange && this.props.onchange(category);

      app.modal.close();

      m.redraw.strategy('none');
    }
  }

  function move() {
    app.modal.show(new MoveDiscussionModal({discussion: this}));
  }

  extend(Discussion.prototype, 'controls', function(items) {
    if (this.canEdit()) {
      items.add('move', ActionButton.component({
        label: 'Move',
        icon: 'arrow-right',
        onclick: move.bind(this)
      }), {after: 'rename'});
    }

    return items;
  });

  override(IndexPage.prototype, 'newDiscussion', function(parent) {
    var categories = app.store.all('categories');
    var slug = this.params().categories;
    var category;
    if (slug || !app.session.user()) {
      parent();
      if (app.composer.component) {
        category = categories.filter(category => category.slug() == slug)[0];
        app.composer.component.category(category);
      }
    } else {
      var modal = new MoveDiscussionModal({onchange: category => {
        parent();
        app.composer.component.category(category);
      }});
      app.modal.show(modal);
    }
  });

  ComposerDiscussion.prototype.chooseCategory = function() {
    var modal = new MoveDiscussionModal({onchange: category => {
      this.category(category);
      this.$('textarea').focus();
    }});
    app.modal.show(modal);
  };

  ComposerDiscussion.prototype.category = m.prop();
  extend(ComposerDiscussion.prototype, 'headerItems', function(items) {
    var category = this.category();

    items.add('category', m('a[href=javascript:;][tabindex=-1].btn.btn-link.control-change-category', {
      onclick: this.chooseCategory.bind(this)
    }, [
      category ? m('span.category-icon', {style: 'background-color: '+category.color()}) : '', ' ',
      m('span.label', category ? category.title() : 'Uncategorized'),
      icon('sort')
    ]));

    return items;
  });

  extend(ComposerDiscussion.prototype, 'data', function(data) {
    data.links = data.links || {};
    data.links.category = this.category();
    return data;
  })

  extend(ActivityPost.prototype, 'headerItems', function(items) {
    var category = this.props.activity.post().discussion().category();
    if (category) {
      items.add('category', m('span.category', {style: {color: category.color()}}, category.title()));
    }
    return items;
  })
});
