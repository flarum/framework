import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import UserControls from 'flarum/forum/utils/UserControls';
import Button from 'flarum/common/components/Button';
import Badge from 'flarum/common/components/Badge';
import User from 'flarum/common/models/User';

import SuspendUserModal from './components/SuspendUserModal';
import checkForSuspension from './checkForSuspension';

export { default as extend } from './extend';

app.initializers.add('flarum-suspend', () => {
  extend(UserControls, 'moderationControls', (items, user) => {
    if (user.canSuspend()) {
      items.add(
        'suspend',
        <Button icon="fas fa-ban" onclick={() => app.modal.show(SuspendUserModal, { user })}>
          {app.translator.trans('flarum-suspend.forum.user_controls.suspend_button')}
        </Button>
      );
    }
  });

  extend(User.prototype, 'badges', function (items) {
    const until = this.suspendedUntil();

    if (new Date() < until) {
      items.add(
        'suspended',
        <Badge icon="fas fa-ban" type="suspended" label={app.translator.trans('flarum-suspend.forum.user_badge.suspended_tooltip')} />,
        100
      );
    }
  });

  checkForSuspension();
});

import './forum';
