import app from '../../forum/app';
import Page, { IPageAttrs } from '../../common/components/Page';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';
import WelcomeHero from './WelcomeHero';
import Dropdown from '../../common/components/Dropdown';
import Button from '../../common/components/Button';
import extractText from '../../common/utils/extractText';
import type Mithril from 'mithril';
import PageStructure from './PageStructure';
import IndexSidebar from './IndexSidebar';
import PostList from './PostList';
import PostListState from '../states/PostListState';

export interface IPostsPageAttrs extends IPageAttrs {}

/**
 * The `PostsPage` component displays the index page, including the welcome
 * hero, the sidebar, and the discussion list.
 */
export default class PostsPage<CustomAttrs extends IPostsPageAttrs = IPostsPageAttrs, CustomState = {}> extends Page<CustomAttrs, CustomState> {
  static providesInitialSearch = true;

  protected posts!: PostListState;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.posts = new PostListState({});

    this.posts.refreshParams(app.search.state.params(), (m.route.param('page') && Number(m.route.param('page'))) || 1);

    app.history.push('posts', extractText(app.translator.trans('core.forum.header.back_to_index_tooltip')));

    this.bodyClass = 'App--posts';
    this.scrollTopOnCreate = false;
  }

  view() {
    return (
      <PageStructure className="PostsPage" hero={this.hero.bind(this)} sidebar={this.sidebar.bind(this)}>
        <div className="IndexPage-toolbar PostsPage-toolbar">
          <ul className="IndexPage-toolbar-view PostsPage-toolbar-view">{listItems(this.viewItems().toArray())}</ul>
          <ul className="IndexPage-toolbar-action PostsPage-toolbar-action">{listItems(this.actionItems().toArray())}</ul>
        </div>
        <PostList state={this.posts} />
      </PageStructure>
    );
  }

  setTitle() {
    app.setTitle(extractText(app.translator.trans('core.forum.posts.meta_title_text')));
    app.setTitleCount(0);
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    this.setTitle();
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    $('#app').css('min-height', '');
  }

  /**
   * Get the component to display as the hero.
   */
  hero() {
    return <WelcomeHero />;
  }

  sidebar() {
    return <IndexSidebar />;
  }

  /**
   * Build an item list for the part of the toolbar which is concerned with how
   * the results are displayed. By default this is just a select box to change
   * the way discussions are sorted.
   */
  viewItems() {
    const items = new ItemList<Mithril.Children>();
    const sortMap = this.posts.sortMap();

    const sortOptions = Object.keys(sortMap).reduce((acc: any, sortId) => {
      acc[sortId] = app.translator.trans(`core.forum.posts_sort.${sortId}_button`);
      return acc;
    }, {});

    if (Object.keys(sortOptions).length > 1) {
      items.add(
        'sort',
        <Dropdown
          buttonClassName="Button"
          label={sortOptions[app.search.state.params().sort] || Object.keys(sortMap).map((key) => sortOptions[key])[0]}
          accessibleToggleLabel={app.translator.trans('core.forum.posts_sort.toggle_dropdown_accessible_label')}
        >
          {Object.keys(sortOptions).map((value) => {
            const label = sortOptions[value];
            const active = (app.search.state.params().sort || Object.keys(sortMap)[0]) === value;

            return (
              <Button icon={active ? 'fas fa-check' : true} onclick={() => app.search.state.changeSort(value)} active={active}>
                {label}
              </Button>
            );
          })}
        </Dropdown>
      );
    }

    return items;
  }

  /**
   * Build an item list for the part of the toolbar which is about taking action
   * on the results. By default this is just a "mark all as read" button.
   */
  actionItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'refresh',
      <Button
        title={app.translator.trans('core.forum.posts.refresh_tooltip')}
        aria-label={app.translator.trans('core.forum.posts.refresh_tooltip')}
        icon="fas fa-sync"
        className="Button Button--icon"
        onclick={() => {
          this.posts.refresh();
          if (app.session.user) {
            app.store.find('users', app.session.user.id()!);
            m.redraw();
          }
        }}
      />
    );

    return items;
  }
}
