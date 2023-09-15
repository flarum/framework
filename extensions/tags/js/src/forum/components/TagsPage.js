import Page from 'flarum/common/components/Page';
import IndexPage from 'flarum/forum/components/IndexPage';
import Link from 'flarum/common/components/Link';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import listItems from 'flarum/common/helpers/listItems';
import ItemList from 'flarum/common/utils/ItemList';
import humanTime from 'flarum/common/helpers/humanTime';
import textContrastClass from 'flarum/common/helpers/textContrastClass';
import classList from 'flarum/common/utils/classList';

import tagIcon from '../../common/helpers/tagIcon';
import tagLabel from '../../common/helpers/tagLabel';
import sortTags from '../../common/utils/sortTags';

export default class TagsPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);

    app.history.push('tags', app.translator.trans('flarum-tags.forum.header.back_to_tags_tooltip'));

    this.tags = [];

    const preloaded = app.preloadedApiDocument();

    if (preloaded) {
      this.tags = sortTags(preloaded.filter((tag) => !tag.isChild()));
      return;
    }

    this.loading = true;

    app.tagList.load(['children', 'lastPostedDiscussion', 'parent']).then(() => {
      this.tags = sortTags(app.store.all('tags').filter((tag) => !tag.isChild()));

      this.loading = false;

      m.redraw();
    });
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    app.setTitle(app.translator.trans('flarum-tags.forum.all_tags.meta_title_text'));
    app.setTitleCount(0);
  }

  view() {
    return <div className="TagsPage">{this.pageContent().toArray()}</div>;
  }

  pageContent() {
    const items = new ItemList();

    items.add('hero', this.hero(), 100);
    items.add('main', <div className="container">{this.mainContent().toArray()}</div>, 10);

    return items;
  }

  mainContent() {
    const items = new ItemList();

    items.add('sidebar', this.sidebar(), 100);
    items.add('content', this.content(), 10);

    return items;
  }

  content() {
    return <div className="TagsPage-content sideNavOffset">{this.contentItems().toArray()}</div>;
  }

  contentItems() {
    const items = new ItemList();

    if (this.loading) {
      items.add('loading', <LoadingIndicator />);
    } else {
      const pinned = this.tags.filter((tag) => tag.position() !== null);
      const cloud = this.tags.filter((tag) => tag.position() === null);

      items.add('tagTiles', this.tagTileListView(pinned), 100);

      if (cloud.length) {
        items.add('cloud', this.cloudView(cloud), 10);
      }
    }

    return items;
  }

  hero() {
    return IndexPage.prototype.hero();
  }

  sidebar() {
    return (
      <nav className="TagsPage-nav IndexPage-nav sideNav">
        <ul>{listItems(this.sidebarItems().toArray())}</ul>
      </nav>
    );
  }

  sidebarItems() {
    return IndexPage.prototype.sidebarItems();
  }

  tagTileListView(pinned) {
    return <ul className="TagTiles">{pinned.map(this.tagTileView.bind(this))}</ul>;
  }

  tagTileView(tag) {
    const lastPostedDiscussion = tag.lastPostedDiscussion();
    const children = sortTags(tag.children() || []);

    return (
      <li className={classList('TagTile', { colored: tag.color() }, textContrastClass(tag.color()))} style={{ '--tag-bg': tag.color() }}>
        <Link className="TagTile-info" href={app.route.tag(tag)}>
          {tag.icon() && tagIcon(tag, {}, { useColor: false })}
          <h3 className="TagTile-name">{tag.name()}</h3>
          <p className="TagTile-description">{tag.description()}</p>
          {!!children && (
            <div className="TagTile-children">{children.map((child) => [<Link href={app.route.tag(child)}>{child.name()}</Link>, ' '])}</div>
          )}
        </Link>
        {lastPostedDiscussion ? (
          <Link className="TagTile-lastPostedDiscussion" href={app.route.discussion(lastPostedDiscussion, lastPostedDiscussion.lastPostNumber())}>
            <span className="TagTile-lastPostedDiscussion-title">{lastPostedDiscussion.title()}</span>
            {humanTime(lastPostedDiscussion.lastPostedAt())}
          </Link>
        ) : (
          <span className="TagTile-lastPostedDiscussion" />
        )}
      </li>
    );
  }

  cloudView(cloud) {
    return <div className="TagCloud">{cloud.map((tag) => [tagLabel(tag, { link: true }), ' '])}</div>;
  }
}
