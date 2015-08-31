import { extend } from 'flarum/extend';
import PermissionGrid from 'flarum/components/PermissionGrid';
import PermissionDropdown from 'flarum/components/PermissionDropdown';
import Dropdown from 'flarum/components/Dropdown';
import Button from 'flarum/components/Button';

import tagLabel from 'tags/helpers/tagLabel';
import tagIcon from 'tags/helpers/tagIcon';
import sortTags from 'tags/utils/sortTags';

export default function() {
  extend(PermissionGrid.prototype, 'scopeItems', items => {
    sortTags(app.store.all('tags'))
      .filter(tag => tag.isRestricted())
      .forEach(tag => items.add('tag' + tag.id(), {
        label: tagLabel(tag),
        onremove: () => tag.save({isRestricted: false}),
        render: item => {
          if (item.permission) {
            let permission;

            if (item.permission === 'forum.view') {
              permission = 'view';
            } else if (item.permission === 'forum.startDiscussion') {
              permission = 'startDiscussion';
            } else if (item.permission.indexOf('discussion.') === 0) {
              permission = item.permission;
            }

            if (permission) {
              const props = Object.assign({}, item);
              props.permission = 'tag' + tag.id() + '.' + permission;

              return PermissionDropdown.component(props);
            }
          }

          return '';
        }
      }));
  });

  extend(PermissionGrid.prototype, 'scopeControlItems', items => {
    const tags = sortTags(app.store.all('tags').filter(tag => !tag.isRestricted()));

    if (tags.length) {
      items.add('tag', Dropdown.component({
        className: 'Dropdown--restrictByTag',
        buttonClassName: 'Button Button--text',
        label: 'Restrict by Tag',
        icon: 'plus',
        caretIcon: null,
        children: tags.map(tag => Button.component({
          icon: true,
          children: [tagIcon(tag, {className: 'Button-icon'}), ' ', tag.name()],
          onclick: () => tag.save({isRestricted: true})
        }))
      }));
    }
  });
}
