import { extend } from 'flarum/extend';
import LinkButton from 'flarum/components/LinkButton';
import IndexPage from 'flarum/components/IndexPage';
import DiscussionList from 'flarum/components/DiscussionList';

export default function addSubscriptionControls() {
  extend(IndexPage.prototype, 'navItems', function(items) {
    if (app.session.user) {
      const params = this.stickyParams();

      params.filter = 'following';

      items.add('following', LinkButton.component({
        href: app.route('index.filter', params),
        children: app.trans('subscriptions.following'),
        icon: 'star'
      }), 50);
    }
  });

  extend(DiscussionList.prototype, 'requestParams', function(params) {
    if (params.filter === 'following') {
      params.q = (params.q || '') + ' is:following';
    }
  });
}
