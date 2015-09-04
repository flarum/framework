import { extend } from 'flarum/extend';
import app from 'flarum/app';
import HeaderSecondary from 'flarum/components/HeaderSecondary';
import ReportsDropdown from 'reports/components/ReportsDropdown';

export default function() {
  extend(HeaderSecondary.prototype, 'items', function(items) {
    if (app.forum.attribute('canViewReports')) {
      items.add('reports', <ReportsDropdown/>, 15);
    }
  });
}
