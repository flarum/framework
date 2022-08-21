import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import LinkButton from 'flarum/common/components/LinkButton';
import IndexPage from 'flarum/forum/components/IndexPage';
import DiscussionListState from 'flarum/forum/states/DiscussionListState';
import GlobalSearchState from 'flarum/forum/states/GlobalSearchState';

export default function addSubscriptionFilter() {
  extend(IndexPage.prototype, 'navItems', function (items) {
    if (app.session.user) {
      const params = app.search.stickyParams();

      items.add(
        'following',
        <LinkButton href={app.route('following', params)} icon="fas fa-star">
          {app.translator.trans('flarum-subscriptions.forum.index.following_link')}
        </LinkButton>,
        50
      );
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
      params.filter ||= {};

      if (params.filter.q) {
        params.filter.q += ' is:following';
      } else {
        params.filter.subscription = 'following';
      }
    }
  });
}
