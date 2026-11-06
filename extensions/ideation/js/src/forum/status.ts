import app from 'flarum/forum/app';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';

/** The board's columns, in order. Declined ideas are listed, not columned. */
export const COLUMNS = ['under_review', 'planned', 'in_progress', 'shipped'];

/** What a moderator can set. Matches Status::SETTABLE on the server. */
export const SETTABLE = [...COLUMNS, 'declined'];

export const ICONS: Record<string, string> = {
  under_review: 'far fa-eye',
  planned: 'far fa-calendar-check',
  in_progress: 'fas fa-person-digging',
  shipped: 'fas fa-rocket',
  declined: 'fas fa-ban',
  merged: 'fas fa-code-merge',
};

export function statusLabel(status: string): Mithril.Children;
export function statusLabel(status: string, text: true): string;
export function statusLabel(status: string, text = false): Mithril.Children {
  const key = 'flarum-ideation.forum.status.' + status;

  return text ? app.translator.trans(key, {}, true) : app.translator.trans(key);
}

/** Voting is open, and merges can land, in these. Matches Status::OPEN on the server. */
export const OPEN = ['under_review', 'planned', 'in_progress'];

export const isOpen = (status: string | null | undefined) => !!status && OPEN.includes(status);

/** One idea's votes on their way: what the member last asked for, and what the server last said. */
type VoteQueue = { want: boolean; server: { voted: boolean; votes: number }; done: Promise<void> };

const queues = new WeakMap<Discussion, VoteQueue>();

/** The count as it will be once the server has the member's latest click. */
function show(discussion: Discussion, queue: VoteQueue) {
  const { voted, votes } = queue.server;

  discussion.pushAttributes({ ideationVoted: queue.want, ideationVotes: Math.max(0, votes + Number(queue.want) - Number(voted)) });
  m.redraw();
}

/**
 * Vote or take a vote back. The button changes at once; the server is sent
 * one change at a time, so quick clicks can never cross on the way and
 * leave a vote the last click took back. Clicks made while a change is on
 * its way are folded into the next one, and an answer or a failure never
 * replaces a newer click on screen: the server's word stands once it has
 * the last one, and a failure puts back what the server last said.
 */
export function toggleVote(discussion: Discussion): Promise<unknown> {
  if (!app.session.user) {
    app.modal.show(() => import('flarum/forum/components/LogInModal'));

    return Promise.resolve();
  }

  const queue = queues.get(discussion);

  if (queue) {
    queue.want = !queue.want;
    show(discussion, queue);

    return queue.done;
  }

  const voted = !!discussion.ideationVoted();
  const created: VoteQueue = { want: !voted, server: { voted, votes: discussion.ideationVotes() || 0 }, done: Promise.resolve() };
  queues.set(discussion, created);
  show(discussion, created);

  created.done = (async () => {
    try {
      while (created.want !== created.server.voted) {
        const want = created.want;

        try {
          await discussion.save({ ideationVoted: want });
        } catch {
          created.want = created.server.voted;
          break;
        }

        created.server = { voted: !!discussion.ideationVoted(), votes: discussion.ideationVotes() || 0 };
        // The server could not take this change (the idea closed, say): do
        // not keep asking it.
        if (created.server.voted !== want) created.want = created.server.voted;
        // The answer went into the store; a click since then still shows.
        show(discussion, created);
      }
    } finally {
      queues.delete(discussion);
      show(discussion, created);
    }
  })();

  return created.done;
}
