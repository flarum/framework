import { extend } from 'flarum/extend';
import app from 'flarum/app';
import HeaderSecondary from 'flarum/components/HeaderSecondary';
import FlagsDropdown from './components/FlagsDropdown';

export default function() {
  extend(HeaderSecondary.prototype, 'items', function(items) {
    if (app.forum.attribute('canViewFlags')) {
      items.add('flags', <FlagsDropdown/>, 15);
    }
  });
}
