import Component from 'flarum/component';
import avatar from 'flarum/helpers/avatar';
import listItems from 'flarum/helpers/list-items';
import highlight from 'flarum/helpers/highlight';
import humanTime from 'flarum/utils/human-time';
import ItemList from 'flarum/utils/item-list';
import abbreviateNumber from 'flarum/utils/abbreviate-number';
import ActionButton from 'flarum/components/action-button';
import DropdownButton from 'flarum/components/dropdown-button';
import LoadingIndicator from 'flarum/components/loading-indicator';
import TerminalPost from 'flarum/components/terminal-post';
import PostPreview from 'flarum/components/post-preview';
import SubtreeRetainer from 'flarum/utils/subtree-retainer';

export default class DiscussionList extends Component {
  constructor(props) {
    super(props);

    this.loading = m.prop(true);
    this.moreResults = m.prop(false);
    this.discussions = m.prop([]);
    this.subtrees = [];

    this.refresh();

    app.session.on('loggedIn', this.loggedInHandler = this.refresh.bind(this));
  }

  params() {
    var params = {include: ['startUser', 'lastUser']};
    for (var i in this.props.params) {
      params[i] = this.props.params[i];
    }
    params.sort = this.sortMap()[params.sort];
    if (params.q) {
      params.include.push('relevantPosts', 'relevantPosts.discussion', 'relevantPosts.user');
    }
    return params;
  }

  willBeRedrawn() {
    this.subtrees.map(subtree => subtree.invalidate());
  }

  sortMap() {
    var map = {};
    if (this.props.params.q) {
      map.relevance = '';
    }
    map.recent = '-lastTime';
    map.replies = '-commentsCount';
    map.newest = '-startTime';
    map.oldest = '+startTime';
    return map;
  }

  refresh() {
    m.startComputation();
    this.loading(true);
    this.discussions([]);
    m.endComputation();
    this.loadResults().then(this.parseResults.bind(this), response => {
      this.loading(false);
      m.redraw();
    });
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

  loadResults(offset) {
    var params = this.params();
    params.page = {offset};
    params.include = params.include.join(',');
    return app.store.find('discussions', params);
  }

  loadMore() {
    var self = this;
    this.loading(true);
    this.loadResults(this.discussions().length).then((results) => this.parseResults(results));
  }

  initSubtree(discussion) {
    this.subtrees[discussion.id()] = new SubtreeRetainer(
      () => discussion.freshness,
      () => app.session.user() && app.session.user().readTime()
    );
  }

  parseResults(results) {
    m.startComputation();
    this.loading(false);

    results.forEach(this.initSubtree.bind(this));

    [].push.apply(this.discussions(), results);
    this.moreResults(!!results.payload.links.next);
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

  addDiscussion(discussion) {
    this.discussions().unshift(discussion);
    this.initSubtree(discussion);
  }

  view() {
    return m('div.discussion-list', [
      m('ul', [
        this.discussions().map(discussion => {
          var startUser = discussion.startUser();
          var isUnread = discussion.isUnread();
          var displayUnread = this.countType() !== 'replies' && isUnread;
          var jumpTo = Math.min(discussion.lastPostNumber(), (discussion.readNumber() || 0) + 1);
          var relevantPosts = this.props.params.q ? discussion.relevantPosts() : '';

          var controls = discussion.controls(this).toArray();

          var active = m.route.param('id') === discussion.id();

          var subtree = this.subtrees[discussion.id()];
          return m('li.discussion-summary'+(isUnread ? '.unread' : '')+(active ? '.active' : ''), {
            key: discussion.id(),
            'data-id': discussion.id()
          }, (subtree && subtree.retain()) || m('div', [
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
              m('h3.title', highlight(discussion.title(), this.props.params.q)),
              m('ul.info', listItems(this.infoItems(discussion).toArray()))
            ]),
            m('span.count', {onclick: this.markAsRead.bind(this, discussion)}, [
              abbreviateNumber(discussion[displayUnread ? 'unreadCount' : 'repliesCount']()),
              m('span.label', displayUnread ? 'unread' : 'replies')
            ]),
            (relevantPosts && relevantPosts.length)
              ? m('div.relevant-posts', relevantPosts.map(post => PostPreview.component({post, highlight: this.props.params.q})))
              : ''
          ]))
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
