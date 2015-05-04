import Component from 'flarum/component';
import avatar from 'flarum/helpers/avatar';
import listItems from 'flarum/helpers/list-items';
import humanTime from 'flarum/utils/human-time';
import ItemList from 'flarum/utils/item-list';
import abbreviateNumber from 'flarum/utils/abbreviate-number';
import ActionButton from 'flarum/components/action-button';
import DropdownButton from 'flarum/components/dropdown-button';
import LoadingIndicator from 'flarum/components/loading-indicator';
import TerminalPost from 'flarum/components/terminal-post';

export default class DiscussionList extends Component {
  constructor(props) {
    super(props);

    this.loading = m.prop(true);
    this.moreResults = m.prop(false);
    this.discussions = m.prop([]);

    this.refresh();

    app.session.on('loggedIn', this.loggedInHandler = this.refresh.bind(this))
  }

  params() {
    var params = {};
    for (var i in this.props.params) {
      params[i] = this.props.params[i];
    }
    params.sort = this.sortMap()[params.sort];
    return params;
  }

  sortMap() {
    return {
      recent: '-lastTime',
      replies: '-commentsCount',
      newest: '-startTime',
      oldest: '+startTime'
    };
  }

  refresh() {
    m.startComputation();
    this.loading(true);
    this.discussions([]);
    m.endComputation();
    this.loadResults().then(this.parseResults.bind(this));
  }

  onunload() {
    app.session.off('loggedIn', this.loggedInHandler);
  }

  terminalPostType() {
    return ['newest', 'oldest'].indexOf(this.props.params.sort) !== -1 ? 'start' : 'last'
  }

  countType() {
    return this.props.params.sort === 'replies' ? 'replies' : 'unread';
  }

  loadResults(start) {
    var params = this.params();
    params.start = start;
    return app.store.find('discussions', params);
  }

  loadMore() {
    var self = this;
    this.loading(true);
    this.loadResults(this.discussions().length).then((results) => this.parseResults(results, true));
  }

  parseResults(results, append) {
    m.startComputation();
    this.loading(false);
    [].push.apply(this.discussions(), results);
    this.moreResults(!!results.meta.moreUrl);
    m.endComputation();
    return results;
  }

  markAsRead(discussion) {
    if (discussion.isUnread()) {
      discussion.save({ readNumber: discussion.lastPostNumber() });
      m.redraw();
    }
  }

  removeDiscussion(discussion) {
    var index = this.discussions().indexOf(discussion);
    if (index !== -1) {
      this.discussions().splice(index, 1);
    }
  }

  view() {
    return m('div', [
      m('ul.discussions-list', [
        this.discussions().map(discussion => {
          var startUser = discussion.startUser();
          var isUnread = discussion.isUnread();
          var displayUnread = this.countType() !== 'replies' && isUnread;
          var jumpTo = Math.min(discussion.lastPostNumber(), (discussion.readNumber() || 0) + 1);

          var controls = discussion.controls(this).toArray();

          var discussionRoute = app.route('discussion', { id: discussion.id(), slug: discussion.slug() });
          var active = m.route().substr(0, discussionRoute.length) === discussionRoute;

          return m('li.discussion-summary'+(isUnread ? '.unread' : '')+(active ? '.active' : ''), {key: discussion.id()}, [
            controls.length ? DropdownButton.component({
              items: controls,
              className: 'contextual-controls',
              buttonClass: 'btn btn-default btn-icon btn-sm btn-naked',
              menuClass: 'pull-right'
            }) : '',
            m((startUser ? 'a' : 'span')+'.author', {
              href: startUser ? app.route('user', { username: startUser.username() }) : undefined,
              config: function(element, isInitialized, context) {
                $(element).tooltip({ placement: 'right' })
                m.route.apply(this, arguments)
              },
              title: 'Started by '+(startUser ? startUser.username() : '[deleted]')+' '+humanTime(discussion.startTime())
            }, [
              avatar(startUser, {title: ''})
            ]),
            m('ul.badges', listItems(discussion.badges().toArray())),
            m('a.main', {href: app.route('discussion.near', {id: discussion.id(), slug: discussion.slug(), near: jumpTo}), config: m.route}, [
              m('h3.title', discussion.title()),
              m('ul.info', listItems(this.infoItems(discussion).toArray()))
            ]),
            m('span.count', {onclick: this.markAsRead.bind(this, discussion)}, [
              abbreviateNumber(discussion[displayUnread ? 'unreadCount' : 'repliesCount']()),
              m('span.label', displayUnread ? 'unread' : 'replies')
            ])
          ])
        })
      ]),
      this.loading()
        ? LoadingIndicator.component()
        : (this.moreResults() ? m('div.load-more', ActionButton.component({
          label: 'Load More',
          className: 'control-loadMore btn btn-default',
          onclick: this.loadMore.bind(this)
        })) : '')
    ]);
  }

  /**
    Build an item list of info for a discussion listing. By default this is
    just the first/last post indicator.

    @return {ItemList}
   */
  infoItems(discussion) {
    var items = new ItemList();

    items.add('terminalPost',
      TerminalPost.component({
        discussion,
        lastPost: this.terminalPostType() !== 'start'
      })
    );

    return items;
  }
}
