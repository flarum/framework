import type Discussion from 'flarum/common/models/Discussion';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
/**
 * Adjust the discussion sidebar for the embed view: drop the scrubber, surface
 * a link back to the real discussion with its comment count, and demote the
 * controls so they don't render as the page's primary control.
 *
 * Exported as a pure function so it can be tested against a synthetic ItemList
 * without booting the full DiscussionPage.
 */
export declare function applyEmbedSidebar(items: ItemList<Mithril.Children>, discussion: Discussion | null): void;
export default function extendDiscussionPage(): void;
