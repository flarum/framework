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

/**
 * Vote or take a vote back. The count moves at once and is put back if the
 * server says no; the server's answer is the one that stays.
 */
export function toggleVote(discussion: Discussion): Promise<unknown> {
  if (!app.session.user) {
    app.modal.show(() => import('flarum/forum/components/LogInModal'));

    return Promise.resolve();
  }

  const voted = !!discussion.ideationVoted();
  const votes = discussion.ideationVotes() || 0;

  discussion.pushAttributes({ ideationVoted: !voted, ideationVotes: Math.max(0, votes + (voted ? -1 : 1)) });
  m.redraw();

  return discussion.save({ ideationVoted: !voted }).catch(() => {
    discussion.pushAttributes({ ideationVoted: voted, ideationVotes: votes });
    m.redraw();
  });
}
