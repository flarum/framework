import type ItemList from 'flarum/common/utils/ItemList';
import type UserSecurityPage from 'flarum/forum/components/UserSecurityPage';
import type Mithril from 'mithril';
/**
 * Lists the applications a member connected on their Security page, between
 * their browser sessions (20) and logging out everywhere (10). Only on their
 * own: moderators can open another member's page, but the connections listed
 * are always the viewer's.
 */
export default function addToSecurityPage(this: UserSecurityPage, items: ItemList<Mithril.Children>): void;
