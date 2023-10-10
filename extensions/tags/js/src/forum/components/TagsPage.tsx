import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';
import type { IPageAttrs } from 'flarum/common/components/Page';
import PageStructure from 'flarum/forum/components/PageStructure';
import WelcomeHero from 'flarum/forum/components/WelcomeHero';
import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import Link from 'flarum/common/components/Link';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import ItemList from 'flarum/common/utils/ItemList';
import humanTime from 'flarum/common/helpers/humanTime';
import textContrastClass from 'flarum/common/helpers/textContrastClass';
import classList from 'flarum/common/utils/classList';
import extractText from 'flarum/common/utils/extractText';

import tagIcon from '../../common/helpers/tagIcon';
import tagLabel from '../../common/helpers/tagLabel';
import sortTags from '../../common/utils/sortTags';
import Mithril from 'mithril';

import type Tag from '../../common/models/Tag';

export interface ITagsPageAttrs extends IPageAttrs {}

export default class TagsPage<CustomAttrs extends ITagsPageAttrs = ITagsPageAttrs> extends Page<CustomAttrs> {
  private tags!: Tag[];
  private loading!: boolean;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    app.history.push('tags', extractText(app.translator.trans('flarum-tags.forum.header.back_to_tags_tooltip')));

    this.tags = [];

    const preloaded = app.preloadedApiDocument<Tag[]>();

    if (preloaded) {
      this.tags = sortTags(preloaded.filter((tag) => !tag.isChild()));
      return;
    }

    this.loading = true;

    app.tagList.load(['children', 'lastPostedDiscussion', 'parent']).then(() => {
      this.tags = sortTags(app.store.all<Tag>('tags').filter((tag) => !tag.isChild()));

      this.loading = false;

      m.redraw();
    });
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    app.setTitle(extractText(app.translator.trans('flarum-tags.forum.all_tags.meta_title_text')));
    app.setTitleCount(0);
  }

  view() {
    return (
      <PageStructure className="TagsPage" hero={this.hero.bind(this)} sidebar={this.sidebar.bind(this)}>
        {this.contentItems().toArray()}
      </PageStructure>
    );
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
    return <WelcomeHero />;
  }

  sidebar() {
    return <IndexSidebar />;
  }

  tagTileListView(pinned: Tag[]) {
    return <ul className="TagTiles">{pinned.map(this.tagTileView.bind(this))}</ul>;
  }

  tagTileView(tag: Tag) {
    const lastPostedDiscussion = tag.lastPostedDiscussion();
    const children = sortTags((tag.children() || []) as Tag[]);

    return (
      <li className={classList('TagTile', { colored: tag.color() }, textContrastClass(tag.color()))} style={{ '--tag-bg': tag.color() }}>
        <Link className="TagTile-info" href={app.route.tag(tag)}>
          <div className="TagTile-heading">
            {tag.icon() && tagIcon(tag, {}, { useColor: false })}
            <h3 className="TagTile-name">{tag.name()}</h3>
          </div>
          <p className="TagTile-description">{tag.description()}</p>
          {!!children && (
            <div className="TagTile-children">{children.map((child) => [<Link href={app.route.tag(child)}>{child.name()}</Link>, ' '])}</div>
          )}
        </Link>
        {lastPostedDiscussion ? (
          <Link className="TagTile-lastPostedDiscussion" href={app.route.discussion(lastPostedDiscussion, lastPostedDiscussion.lastPostNumber()!)}>
            <span className="TagTile-lastPostedDiscussion-title">{lastPostedDiscussion.title()}</span>
            {humanTime(lastPostedDiscussion.lastPostedAt()!)}
          </Link>
        ) : (
          <span className="TagTile-lastPostedDiscussion" />
        )}
      </li>
    );
  }

  cloudView(cloud: Tag[]) {
    return <div className="TagCloud">{cloud.map((tag) => [tagLabel(tag, { link: true }), ' '])}</div>;
  }
}
