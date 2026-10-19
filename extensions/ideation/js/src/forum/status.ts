import app from 'flarum/forum/app';
import type Discussion from 'flarum/common/models/Discussion';
import { t } from './t';

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

export const statusLabel = (status: string) => t('status.' + status);

export const isOpen = (status: string | null) => status === 'under_review' || status === 'planned' || status === 'in_progress';

/**
 * Vote or take a vote back. The count moves at once and is put back if the
 * server says no; the server's answer is the one that stays.
 */
export function toggleVote(discussion: Discussion): Promise<unknown> {
  if (!app.session.user) {
    app.modal.show(() => import('flarum/forum/components/LogInModal'));

    return Promise.resolve();
  }

  const voted = !!discussion.attribute('ideationVoted');
  const votes = discussion.attribute<number>('ideationVotes') || 0;

  discussion.pushAttributes({ ideationVoted: !voted, ideationVotes: Math.max(0, votes + (voted ? -1 : 1)) });
  m.redraw();

  return discussion.save({ ideationVoted: !voted }).catch(() => {
    discussion.pushAttributes({ ideationVoted: voted, ideationVotes: votes });
    m.redraw();
  });
}
