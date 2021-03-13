import { extend } from 'flarum/extend';
import LinkButton from 'flarum/components/LinkButton';
import IndexPage from 'flarum/components/IndexPage';
import DiscussionListState from 'flarum/states/DiscussionListState';
import GlobalSearchState from 'flarum/states/GlobalSearchState';

export default function addSubscriptionFilter() {
  extend(IndexPage.prototype, 'navItems', function(items) {
    if (app.session.user) {
      const params = app.search.stickyParams();

      items.add('following', LinkButton.component({
        href: app.route('following', params),
        icon: 'fas fa-star'
      }, app.translator.trans('flarum-subscriptions.forum.index.following_link')), 50);
    }
  });

  extend(IndexPage.prototype, 'setTitle', function () {
    if (app.current.get('routeName') === 'following') {
      app.setTitle(app.translator.trans('flarum-subscriptions.forum.following.meta_title_text'));
    }
  });

  extend(GlobalSearchState.prototype, 'params', function (params) {
    // We can't set `q` here directly, as that would make the search bar
    // think that text has been entered, and display the "clear" button.
    params.onFollowing = app.current.get('routeName') === 'following';
  });

  extend(DiscussionListState.prototype, 'requestParams', function (params) {
    if (this.params.onFollowing) {
      params.filter.subscription = 'following';
    }
  });
}
