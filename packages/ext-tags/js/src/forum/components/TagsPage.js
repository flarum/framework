import Page from 'flarum/components/Page';
import IndexPage from 'flarum/components/IndexPage';
import Link from 'flarum/components/Link';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import listItems from 'flarum/helpers/listItems';
import humanTime from 'flarum/helpers/humanTime';

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
      this.tags = sortTags(preloaded.filter(tag => !tag.isChild()));
      return;
    }

    this.loading = true;

    app.tagList.load(['children', 'lastPostedDiscussion', 'parent']).then(() => {
      this.tags = sortTags(app.store.all('tags').filter(tag => !tag.isChild()));

      this.loading = false;

      m.redraw();
    });
  }

  view() {
    if (this.loading) {
      return <LoadingIndicator />;
    }

    const pinned = this.tags.filter(tag => tag.position() !== null);
    const cloud = this.tags.filter(tag => tag.position() === null);

    return (
      <div className="TagsPage">
        {IndexPage.prototype.hero()}
        <div className="container">
          <nav className="TagsPage-nav IndexPage-nav sideNav">
            <ul>{listItems(IndexPage.prototype.sidebarItems().toArray())}</ul>
          </nav>

          <div className="TagsPage-content sideNavOffset">
            <ul className="TagTiles">
              {pinned.map(tag => {
                const lastPostedDiscussion = tag.lastPostedDiscussion();
                const children = sortTags(tag.children() || []);

                return (
                  <li className={'TagTile ' + (tag.color() ? 'colored' : '')}
                    style={{ '--tag-bg': tag.color() }}>
                    <Link className="TagTile-info" href={app.route.tag(tag)}>
                      {tag.icon() && tagIcon(tag, {}, { useColor: false })}
                      <h3 className="TagTile-name">{tag.name()}</h3>
                      <p className="TagTile-description">{tag.description()}</p>
                      {children
                        ? (
                          <div className="TagTile-children">
                            {children.map(child => [
                              <Link href={app.route.tag(child)}>
                                {child.name()}
                              </Link>,
                              ' '
                            ])}
                          </div>
                        ) : ''}
                    </Link>
                    {lastPostedDiscussion
                      ? (
                        <Link className="TagTile-lastPostedDiscussion"
                          href={app.route.discussion(lastPostedDiscussion, lastPostedDiscussion.lastPostNumber())}
                          >
                          <span className="TagTile-lastPostedDiscussion-title">{lastPostedDiscussion.title()}</span>
                          {humanTime(lastPostedDiscussion.lastPostedAt())}
                        </Link>
                      ) : (
                        <span className="TagTile-lastPostedDiscussion"/>
                      )}
                  </li>
                );
              })}
            </ul>

            {cloud.length ? (
              <div className="TagCloud">
                {cloud.map(tag => [
                  tagLabel(tag, {link: true}),
                  ' ',
                ])}
              </div>
            ) : ''}
          </div>
        </div>
      </div>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    app.setTitle(app.translator.trans('flarum-tags.forum.all_tags.meta_title_text'));
    app.setTitleCount(0);
  }
}
