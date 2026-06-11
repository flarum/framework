import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Button from 'flarum/common/components/Button';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import UserControls from 'flarum/forum/utils/UserControls';
import SessionDropdown from 'flarum/forum/components/SessionDropdown';
import ActorAuditModal from './components/ActorAuditModal';
import DiscussionAuditModal from './components/DiscussionAuditModal';
import UserAuditModal from './components/UserAuditModal';
import AuditModal from './components/AuditModal';

export { default as extend } from './extend';

app.initializers.add('flarum-audit', () => {
  extend(DiscussionControls, 'moderationControls', function (items, discussion) {
    if (!app.forum.attribute('canAuditDiscussion')) {
      return;
    }

    items.add(
      'flarum-audit-user',
      <Button icon="fas fa-book" onclick={() => app.modal.show(DiscussionAuditModal, { discussion })}>
        {app.translator.trans('flarum-audit.forum.link.discussion-audit')}
      </Button>
    );
  });

  extend(UserControls, 'moderationControls', function (items, user) {
    if (!app.forum.attribute('canAuditUser')) {
      return;
    }

    items.add(
      'flarum-audit-user',
      <Button icon="fas fa-book" onclick={() => app.modal.show(UserAuditModal, { user })}>
        {app.translator.trans('flarum-audit.forum.link.user-audit')}
      </Button>
    );
  });

  extend(UserControls, 'moderationControls', function (items, user) {
    if (!app.forum.attribute('canAudit')) {
      return;
    }

    items.add(
      'flarum-audit-actor',
      <Button icon="fas fa-book" onclick={() => app.modal.show(ActorAuditModal, { user })}>
        {app.translator.trans('flarum-audit.forum.link.actor-audit')}
      </Button>
    );
  });

  extend(SessionDropdown.prototype, 'items', function (items) {
    if (!app.forum.attribute('canAudit')) {
      return;
    }

    items.add(
      'flarum-audit',
      <Button icon="fas fa-book" onclick={() => app.modal.show(AuditModal)}>
        {app.translator.trans('flarum-audit.forum.link.all-audit')}
      </Button>,
      20
    ); // Value above zero, so it doesn't end up below the admin link
  });
});
