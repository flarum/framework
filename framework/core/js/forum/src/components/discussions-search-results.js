import highlight from 'flarum/helpers/highlight';
import truncate from 'flarum/utils/truncate';
import ActionButton from 'flarum/components/action-button';

export default class DiscussionsSearchResults {
  constructor() {
    this.results = {};
  }

  search(string) {
    this.results[string] = [];
    return app.store.find('discussions', {q: string, page: {limit: 3}, include: 'relevantPosts,relevantPosts.discussion'}).then(results => {
      this.results[string] = results;
    });
  }

  view(string) {
    return [
      m('li.dropdown-header', 'Discussions'),
      m('li', ActionButton.component({
        icon: 'search',
        label: 'Search all discussions for "'+string+'"',
        href: app.route('index', {q: string}),
        config: m.route
      })),
      (this.results[string] && this.results[string].length) ? this.results[string].map(discussion => {
        var relevantPosts = discussion.relevantPosts();
        var post = relevantPosts && relevantPosts[0];
        return m('li.discussion-search-result', {'data-index': 'discussions'+discussion.id()},
          m('a', { href: app.route.discussion(discussion, post && post.number()), config: m.route },
            m('div.title', highlight(discussion.title(), string)),
            post ? m('div.excerpt', highlight(truncate(post.contentPlain(), 100), string)) : ''
          )
        );
      }) : ''
    ];
  }
}
