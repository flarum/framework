import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import HeaderSecondary from 'flarum/forum/components/HeaderSecondary';
import FlagsDropdown from './components/FlagsDropdown';

export default function () {
  extend(HeaderSecondary.prototype, 'items', function (items) {
    if (app.forum.attribute('canViewFlags')) {
      items.add('flags', <FlagsDropdown state={app.flags} />, 15);
    }
  });
}
