import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import UserControls from 'flarum/forum/utils/UserControls';
import Button from 'flarum/common/components/Button';

export default function () {
  extend(UserControls, 'userControls', function (items, user) {
    if (!user.canFlagUser()) return;

    items.add(
      'flagUser',
      <Button icon="fas fa-flag" onclick={() => app.modal.show(() => import('./components/FlagUserModal'), { user })}>
        {app.translator.trans('flarum-flags.forum.user_controls.flag_button')}
      </Button>
    );
  });

  extend(UserControls, 'moderationControls', function (items, user) {
    if (!user.canViewUserFlags()) return;
    const flags = user.flags();
    if (!flags || !flags.length) return;

    items.add(
      'viewUserFlags',
      <Button icon="fas fa-flag" onclick={() => app.modal.show(() => import('./components/UserFlagsModal'), { user })}>
        {app.translator.trans('flarum-flags.forum.user_controls.view_flags_button')}
      </Button>
    );
  });
}
