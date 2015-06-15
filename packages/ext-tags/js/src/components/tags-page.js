import Component from 'flarum/component';
import WelcomeHero from 'flarum/components/welcome-hero';
import IndexPage from 'flarum/components/index-page';
import icon from 'flarum/helpers/icon';
import listItems from 'flarum/helpers/list-items';
import abbreviateNumber from 'flarum/utils/abbreviate-number';
import humanTime from 'flarum/helpers/human-time';

import sortTags from 'flarum-tags/utils/sort-tags';

export default class TagsPage extends Component {
  constructor(props) {
    super(props);

    this.tags = sortTags(app.store.all('tags').filter(tag => !tag.parent()));

    app.current = this;
    app.history.push('tags');
  }

  view() {
    var pinned = this.tags.filter(tag => tag.position() !== null);
    var cloud = this.tags.filter(tag => tag.position() === null);

    return m('div.tags-area', {config: this.onload.bind(this)}, [
      IndexPage.prototype.hero(),
      m('div.container', [
        m('nav.side-nav.index-nav', [
          m('ul', listItems(IndexPage.prototype.sidebarItems().toArray()))
        ]),
        m('div.offset-content.tags-content', [
          m('ul.tag-tiles', [
            pinned.map(tag => {
              var lastDiscussion = tag.lastDiscussion();
              var children = app.store.all('tags').filter(child => {
                var parent = child.parent();
                return parent && parent.id() == tag.id();
              });

              return m('li.tag-tile', {style: 'background-color: '+tag.color()}, [
                m('a.tag-info', {href: app.route.tag(tag), config: m.route}, [
                  m('h3.name', tag.name()),
                  m('p.description', tag.description()),
                  children ? m('div.children', children.map(tag =>
                    m('a', {href: app.route.tag(tag), config: m.route, onclick: (e) => e.stopPropagation()}, tag.name())
                  )) : ''
                ]),
                lastDiscussion
                  ? m('a.last-discussion', {
                    href: app.route.discussion(lastDiscussion, lastDiscussion.lastPostNumber()),
                    config: m.route
                  }, [m('span.title', lastDiscussion.title()), humanTime(lastDiscussion.lastTime())])
                  : m('span.last-discussion')
              ]);
            })
          ]),
          m('div.tag-cloud', [
            m('h4', 'Tags'),
            m('div.tag-cloud-content', cloud.map(tag =>
              m('a', {href: app.route.tag(tag), config: m.route, style: tag.color() ? 'color: '+tag.color() : ''}, tag.name())
            ))
          ])
        ])
      ])
    ]);
  }

  onload(element, isInitialized, context) {
    IndexPage.prototype.onload.apply(this, arguments);
  }

  onunload() {
    IndexPage.prototype.onunload.apply(this);
  }
}
