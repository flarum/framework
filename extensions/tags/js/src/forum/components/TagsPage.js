import Page from 'flarum/components/Page';
import IndexPage from 'flarum/components/IndexPage';
import listItems from 'flarum/helpers/listItems';
import humanTime from 'flarum/helpers/humanTime';

import tagLabel from '../../common/helpers/tagLabel';
import sortTags from '../../common/utils/sortTags';

export default class TagsPage extends Page {
  init() {
    super.init();

    this.tags = sortTags(app.store.all('tags').filter(tag => !tag.parent()));

    app.history.push('tags', app.translator.trans('flarum-tags.forum.header.back_to_tags_tooltip'));
  }

  view() {
    const pinned = this.tags.filter(tag => tag.position() !== null);
    const cloud = this.tags.filter(tag => tag.position() === null);

    return (
      <div className="TagsPage">
        {IndexPage.prototype.hero()}
        <div className="container">
          <nav className="TagsPage-nav IndexPage-nav sideNav" config={IndexPage.prototype.affixSidebar}>
            <ul>{listItems(IndexPage.prototype.sidebarItems().toArray())}</ul>
          </nav>

          <div className="TagsPage-content sideNavOffset">
            <ul className="TagTiles">
              {pinned.map(tag => {
                const lastPostedDiscussion = tag.lastPostedDiscussion();
                const children = sortTags(app.store.all('tags').filter(child => child.parent() === tag));

                return (
                  <li className={'TagTile ' + (tag.color() ? 'colored' : '')}
                    style={{backgroundColor: tag.color()}}>
                    <a className="TagTile-info" href={app.route.tag(tag)} config={m.route}>
                      <h3 className="TagTile-name">{tag.name()}</h3>
                      <p className="TagTile-description">{tag.description()}</p>
                      {children
                        ? (
                          <div className="TagTile-children">
                            {children.map(child => [
                              <a href={app.route.tag(child)} config={function(element, isInitialized) {
                                if (isInitialized) return;
                                $(element).on('click', e => e.stopPropagation());
                                m.route.apply(this, arguments);
                              }}>
                                {child.name()}
                              </a>,
                              ' '
                            ])}
                          </div>
                        ) : ''}
                    </a>
                    {lastPostedDiscussion
                      ? (
                        <a className="TagTile-lastPostedDiscussion"
                          href={app.route.discussion(lastPostedDiscussion, lastPostedDiscussion.lastPostNumber())}
                          config={m.route}>
                          <span className="TagTile-lastPostedDiscussion-title">{lastPostedDiscussion.title()}</span>
                          {humanTime(lastPostedDiscussion.lastPostedAt())}
                        </a>
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

  config(...args) {
    super.config(...args);

    app.setTitle(app.translator.trans('flarum-tags.forum.all_tags.meta_title_text'));
    app.setTitleCount(0);
  }
}
