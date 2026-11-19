import app from 'flarum/forum/app';
import { extend, override } from 'flarum/common/extend';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
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
export function applyEmbedSidebar(items: ItemList<Mithril.Children>, discussion: Discussion | null): void {
  items.remove('scrubber');

  if (discussion) {
    const count = discussion.replyCount() ?? 0;

    items.add(
      'replies',
      <h3>
        <a href={app.route.discussion(discussion).replace('/embed', '/d')} target="_blank">
          {count} comment{count === 1 ? '' : 's'}
        </a>
      </h3>,
      100
    );
  }

  const controls = items.get('controls') as Mithril.Vnode<any> | undefined;

  if (controls?.attrs?.className) {
    controls.attrs.className = controls.attrs.className.replace('App-primaryControl', '');
  }
}

export default function extendDiscussionPage(): void {
  // The embed shows a single discussion and hides the header that holds the
  // pane's pin. A pin saved while reading the forum shares this origin's
  // storage, so without this a wide embed would load the forum's discussion
  // list beside the discussion, with no way to unpin it from here. Only the
  // in-memory flag changes: the forum keeps the reader's choice.
  override(DiscussionPage.prototype, 'oninit', function (this: DiscussionPage, original: (...args: any[]) => void, ...args: any[]) {
    if (app.pane) app.pane.pinned = false;

    return original(...args);
  });

  extend(DiscussionPage.prototype, 'sidebarItems', function (this: DiscussionPage, items: ItemList<Mithril.Children>) {
    // `discussion` is protected on DiscussionPage; access via a cast.
    applyEmbedSidebar(items, (this as any).discussion);
  });
}
