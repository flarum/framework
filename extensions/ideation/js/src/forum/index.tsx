import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Discussion from 'flarum/common/models/Discussion';
import Badge from 'flarum/common/components/Badge';
import Button from 'flarum/common/components/Button';
import LinkButton from 'flarum/common/components/LinkButton';
import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import DiscussionListItem from 'flarum/forum/components/DiscussionListItem';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import type DiscussionComposer from 'flarum/forum/components/DiscussionComposer';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

import VoteButton from './components/VoteButton';
import IdeationPanel from './components/IdeationPanel';
import { ICONS, statusLabel } from './status';

export { default as extend } from './extend';

const isIdea = (discussion: Discussion) => !!discussion.ideationStatus();

type IdeationComposerAttrs = { ideationIdea?: boolean };
type NotificationType = { name: string; icon: string; label: Mithril.Children };

app.initializers.add('flarum-ideation', () => {
  /*
   * IndexSidebar, not IndexPage: Flarum 2 moved the left-hand nav into its
   * own component, and the old hook adds nothing without an error.
   */
  extend(IndexSidebar.prototype, 'navItems', (items) => {
    items.add(
      'ideation',
      <LinkButton href={app.route('ideation')} icon="fas fa-map-signs">
        {app.translator.trans('flarum-ideation.forum.title')}
      </LinkButton>,
      85
    );
  });

  // The idea's status wherever the discussion is shown, as a stock badge.
  extend(Discussion.prototype, 'badges', function (this: Discussion, items) {
    const status = this.ideationStatus();
    if (!status) return;

    items.add('ideation', <Badge type={'ideation IdeationStatus--' + status} icon={ICONS[status]} label={statusLabel(status)} tabindex="0" />, 5);
  });

  // In any discussion list, an idea carries its vote next to its replies.
  extend(DiscussionListItem.prototype, 'statsItems', function (this: DiscussionListItem, items) {
    const discussion = this.attrs.discussion;
    if (!isIdea(discussion) || discussion.ideationStatus() === 'merged') return;

    items.add('ideationVotes', <VoteButton discussion={discussion} />, 80);
  });

  extend(DiscussionPage.prototype, 'sidebarItems', function (this: DiscussionPage, items) {
    const discussion = this.discussion;
    if (!discussion || !isIdea(discussion)) return;

    items.add('ideation', <IdeationPanel discussion={discussion} />, 110);
  });

  extend(DiscussionControls, 'moderationControls', (items, discussion: Discussion) => {
    if (!discussion.canIdeationModerate()) return;

    items.add(
      'ideation',
      <Button icon="fas fa-map-signs" onclick={() => app.modal.show(() => import('./components/IdeationModal'), { discussion })}>
        {app.translator.trans(isIdea(discussion) ? 'flarum-ideation.forum.controls.manage' : 'flarum-ideation.forum.controls.add')}
      </Button>
    );
  });

  /*
   * "Post an idea" opens the ordinary discussion composer with this flag, so
   * tags, drafts, previews and every other composer extension still apply.
   * By module name: DiscussionComposer is a lazy chunk in Flarum 2, and
   * extending a static import of it does nothing.
   */
  extend('flarum/forum/components/DiscussionComposer', 'data', function (this: DiscussionComposer, data: Record<string, unknown>) {
    if ((this.attrs as IdeationComposerAttrs).ideationIdea) data.ideationStatus = 'under_review';
  });

  extend('flarum/forum/components/NotificationGrid', 'notificationTypes', (items: ItemList<NotificationType>) => {
    items.add('ideationShipped', {
      name: 'ideationShipped',
      icon: 'fas fa-rocket',
      label: app.translator.trans('flarum-ideation.forum.notifications.shipped_setting'),
    });
  });
});
