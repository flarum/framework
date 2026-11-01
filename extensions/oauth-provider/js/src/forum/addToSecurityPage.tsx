import app from 'flarum/forum/app';
import FieldSet from 'flarum/common/components/FieldSet';
import type ItemList from 'flarum/common/utils/ItemList';
import type UserSecurityPage from 'flarum/forum/components/UserSecurityPage';
import type Mithril from 'mithril';

import ConnectedApplications from './components/ConnectedApplications';

/**
 * Lists the applications a member connected on their Security page, between
 * their browser sessions (20) and logging out everywhere (10). Only on their
 * own: moderators can open another member's page, but the connections listed
 * are always the viewer's.
 */
export default function addToSecurityPage(this: UserSecurityPage, items: ItemList<Mithril.Children>) {
  if (!this.user || this.user.id() !== app.session.user?.id()) return;

  items.add(
    'oauthConnectedApplications',
    <FieldSet
      className="UserSecurityPage-connectedApplications"
      label={app.translator.trans('flarum-oauth-provider.forum.connected_apps.heading')}
      description={app.translator.trans('flarum-oauth-provider.forum.connected_apps.help_text')}
    >
      <ConnectedApplications />
    </FieldSet>,
    15
  );
}
