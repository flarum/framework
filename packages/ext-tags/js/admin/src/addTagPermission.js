import { extend } from 'flarum/extend';
import PermissionGrid from 'flarum/components/PermissionGrid';

export default function() {
  extend(PermissionGrid.prototype, 'moderateItems', items => {
    items.add('tag', {
      icon: 'tag',
      label: 'Tag discussions',
      permission: 'discussion.tag'
    }, 95);
  });
}
