import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import HeaderSecondary from 'flarum/forum/components/HeaderSecondary';
import ErasureRequestsDropdown from '../components/ErasureRequestsDropdown';

export default function extendHeaderSecondary() {
  extend(HeaderSecondary.prototype, 'items', function (items) {
    if (app.forum.attribute<number>('erasureRequestCount')) {
      items.add('erasureRequests', <ErasureRequestsDropdown state={app.erasureRequests} />, 20);
    }
  });
}
