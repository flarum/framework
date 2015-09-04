import { extend } from 'flarum/extend';
import app from 'flarum/app';
import PermissionGrid from 'flarum/components/PermissionGrid';

app.initializers.add('reports', () => {
  extend(PermissionGrid.prototype, 'moderateItems', items => {
    items.add('viewReports', {
      label: 'View reported posts',
      permission: 'discussion.viewReports'
    });
  });

  extend(PermissionGrid.prototype, 'replyItems', items => {
    items.add('reportPosts', {
      label: 'Report posts',
      permission: 'discussion.reportPosts'
    });
  });
});
