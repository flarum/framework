import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Page from 'flarum/common/components/Page';

export default function extendPage() {
  extend(Page.prototype, 'oninit', function () {
    if (m.route.param('erasureRequestConfirmed')) {
      app.alerts.show({ type: 'success' }, app.translator.trans('flarum-gdpr.forum.erasure_request_confirmed'));
    }
  });
}
