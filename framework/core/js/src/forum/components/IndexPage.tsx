import app from '../../forum/app';
import Page, { IPageAttrs } from '../../common/components/Page';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';
import DiscussionList from './DiscussionList';
import WelcomeHero from './WelcomeHero';
import DiscussionPage from './DiscussionPage';
import Dropdown from '../../common/components/Dropdown';
import Button from '../../common/components/Button';
import extractText from '../../common/utils/extractText';
import type Mithril from 'mithril';
import type Discussion from '../../common/models/Discussion';
import PageStructure from './PageStructure';
import IndexSidebar from './IndexSidebar';

export interface IIndexPageAttrs extends IPageAttrs {}

/**
 * The `IndexPage` component displays the index page, including the welcome
 * hero, the sidebar, and the discussion list.
 */
export default class IndexPage<CustomAttrs extends IIndexPageAttrs = IIndexPageAttrs, CustomState = {}> extends Page<CustomAttrs, CustomState> {
  static providesInitialSearch = true;
  lastDiscussion?: Discussion;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    // If the user is returning from a discussion page, then take note of which
    // discussion they have just visited. After the view is rendered, we will
    // scroll down so that this discussion is in view.
    if (app.previous.matches(DiscussionPage)) {
      this.lastDiscussion = app.previous.get('discussion');
    }

    // If the user is coming from the discussion list, then they have either
    // just switched one of the parameters (filter, sort, search) or they
    // probably want to refresh the results. We will clear the discussion list
    // cache so that results are reloaded.
    if (app.previous.matches(IndexPage)) {
      app.discussions.clear();
    }

    app.discussions.refreshParams(app.search.state.params(), (m.route.param('page') && Number(m.route.param('page'))) || 1);

    app.history.push('index', extractText(app.translator.trans('core.forum.header.back_to_index_tooltip')));

    this.bodyClass = 'App--index';
    this.scrollTopOnCreate = false;
  }

  view() {
    return (
      <PageStructure className="IndexPage" hero={this.hero.bind(this)} sidebar={this.sidebar.bind(this)}>
        <div className="IndexPage-toolbar">
          <ul className="IndexPage-toolbar-view">{listItems(this.viewItems().toArray())}</ul>
          <ul className="IndexPage-toolbar-action">{listItems(this.actionItems().toArray())}</ul>
        </div>
        <DiscussionList state={app.discussions} />
      </PageStructure>
    );
  }

  setTitle() {
    app.setTitle(extractText(app.translator.trans('core.forum.index.meta_title_text')));
    app.setTitleCount(0);
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    this.setTitle();

    // Work out the difference between the height of this hero and that of the
    // previous hero. Maintain the same scroll position relative to the bottom
    // of the hero so that the sidebar doesn't jump around.
    const oldHeroHeight = app.cache.heroHeight as number;
    const heroHeight = (app.cache.heroHeight = this.$('.Hero').outerHeight() || 0);
    const scrollTop = app.cache.scrollTop as number;

    $('#app').css('min-height', ($(window).height() || 0) + heroHeight);

    // Let browser handle scrolling on page reload.
    if (app.previous.type == null) return;

    // Only retain scroll position if we're coming from a discussion page.
    // Otherwise, we've just changed the filter, so we should go to the top of the page.
    if (this.lastDiscussion) {
      $(window).scrollTop(scrollTop - oldHeroHeight + heroHeight);
    } else {
      $(window).scrollTop(0);
    }

    // If we've just returned from a discussion page, then the constructor will
    // have set the `lastDiscussion` property. If this is the case, we want to
    // scroll down to that discussion so that it's in view.
    if (this.lastDiscussion) {
      const $discussion = this.$(`li[data-id="${this.lastDiscussion.id()}"] .DiscussionListItem`);

      if ($discussion.length) {
        const indexTop = $('#header').outerHeight() || 0;
        const indexBottom = $(window).height() || 0;
        const discussionOffset = $discussion.offset();
        const discussionTop = (discussionOffset && discussionOffset.top) || 0;
        const discussionBottom = discussionTop + ($discussion.outerHeight() || 0);

        if (discussionTop < scrollTop + indexTop || discussionBottom > scrollTop + indexBottom) {
          $(window).scrollTop(discussionTop - indexTop);
        }
      }
    }
  }

  onbeforeremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onbeforeremove(vnode);

    // Save the scroll position so we can restore it when we return to the
    // discussion list.
    app.cache.scrollTop = $(window).scrollTop();
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
    const sortMap = app.discussions.sortMap();

    const sortOptions = Object.keys(sortMap).reduce((acc: any, sortId) => {
      acc[sortId] = app.translator.trans(`core.forum.index_sort.${sortId}_button`);
      return acc;
    }, {});

    items.add(
      'sort',
      <Dropdown
        buttonClassName="Button"
        label={sortOptions[app.search.state.params().sort] || Object.keys(sortMap).map((key) => sortOptions[key])[0]}
        accessibleToggleLabel={app.translator.trans('core.forum.index_sort.toggle_dropdown_accessible_label')}
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
        title={app.translator.trans('core.forum.index.refresh_tooltip')}
        aria-label={app.translator.trans('core.forum.index.refresh_tooltip')}
        icon="fas fa-sync"
        className="Button Button--icon"
        onclick={() => {
          app.discussions.refresh();
          if (app.session.user) {
            app.store.find('users', app.session.user.id()!);
            m.redraw();
          }
        }}
      />
    );

    if (app.session.user) {
      items.add(
        'markAllAsRead',
        <Button
          title={app.translator.trans('core.forum.index.mark_all_as_read_tooltip')}
          aria-label={app.translator.trans('core.forum.index.mark_all_as_read_tooltip')}
          icon="fas fa-check"
          className="Button Button--icon"
          onclick={this.markAllAsRead.bind(this)}
        />
      );
    }

    return items;
  }

  /**
   * Mark all discussions as read.
   */
  markAllAsRead() {
    const confirmation = confirm(extractText(app.translator.trans('core.forum.index.mark_all_as_read_confirmation')));

    if (confirmation) {
      app.session.user?.save({ markedAllAsReadAt: new Date() });
    }
  }
}
