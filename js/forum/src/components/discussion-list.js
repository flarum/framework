import Component from 'flarum/component';
import DiscussionListItem from 'flarum/components/discussion-list-item';
import ActionButton from 'flarum/components/action-button';
import LoadingIndicator from 'flarum/components/loading-indicator';

export default class DiscussionList extends Component {
  constructor(props) {
    super(props);

    this.loading = m.prop(true);
    this.moreResults = m.prop(false);
    this.discussions = m.prop([]);

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
    const discussions = app.preloadedDocument();

    if (discussions) {
      return m.deferred().resolve(discussions).promise;
    } else {
      var params = this.params();
      params.page = {offset};
      params.include = params.include.join(',');
      return app.store.find('discussions', params);
    }
  }

  loadMore() {
    var self = this;
    this.loading(true);
    this.loadResults(this.discussions().length).then((results) => this.parseResults(results));
  }

  parseResults(results) {
    m.startComputation();
    this.loading(false);

    [].push.apply(this.discussions(), results);
    this.moreResults(!!results.payload.links.next);
    m.endComputation();
    return results;
  }

  removeDiscussion(discussion) {
    var index = this.discussions().indexOf(discussion);
    if (index !== -1) {
      this.discussions().splice(index, 1);
    }
  }

  addDiscussion(discussion) {
    this.discussions().unshift(discussion);
  }

  view() {
    return m('div.discussion-list', [
      m('ul', [
        this.discussions().map(discussion => {
          return m('li', {
            key: discussion.id(),
            'data-id': discussion.id()
          }, DiscussionListItem.component({
            discussion,
            q: this.props.params.q,
            countType: this.countType(),
            terminalPostType: this.terminalPostType()
          }));
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
}
