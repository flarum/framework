import { extend } from 'flarum/extend';
import app from 'flarum/app';
import PermissionGrid from 'flarum/components/PermissionGrid';

import FlagsSettingsModal from './components/FlagsSettingsModal';

app.initializers.add('flarum-flags', () => {
  app.extensionSettings['flarum-flags'] = () => app.modal.show(new FlagsSettingsModal());

  extend(PermissionGrid.prototype, 'moderateItems', items => {
    items.add('viewFlags', {
      icon: 'fas fa-flag',
      label: app.translator.trans('flarum-flags.admin.permissions.view_flags_label'),
      permission: 'discussion.viewFlags'
    }, 65);
  });

  extend(PermissionGrid.prototype, 'replyItems', items => {
    items.add('flagPosts', {
      icon: 'fas fa-flag',
      label: app.translator.trans('flarum-flags.admin.permissions.flag_posts_label'),
      permission: 'discussion.flagPosts'
    }, 70);
  });
});

// Expose compat API
import flagsCompat from './compat';
import { compat } from '@flarum/core/admin';

Object.assign(compat, flagsCompat);
