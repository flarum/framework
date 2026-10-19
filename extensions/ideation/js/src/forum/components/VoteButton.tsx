import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Icon from 'flarum/common/components/Icon';
import classList from 'flarum/common/utils/classList';
import extractText from 'flarum/common/utils/extractText';
import type Discussion from 'flarum/common/models/Discussion';
import { t } from '../t';
import { isOpen, toggleVote } from '../status';

export interface VoteButtonAttrs extends ComponentAttrs {
  discussion: Discussion;
  /** A wide button with a label, for the discussion page sidebar. */
  wide?: boolean;
  className?: string;
}

/**
 * The upvote. A guest may press it (and is asked to log in); a member who
 * cannot vote here, or an idea that is closed, shows the count only.
 */
export default class VoteButton extends Component<VoteButtonAttrs> {
  view() {
    const discussion = this.attrs.discussion;
    const voted = !!discussion.attribute('ideationVoted');
    const votes = discussion.attribute<number>('ideationVotes') || 0;
    // A guest is offered the button (and asked to log in) only where voting is
    // open; the server's answer decides for everyone else.
    const usable = app.session.user ? !!discussion.attribute('canIdeationVote') : isOpen(discussion.attribute('ideationStatus'));

    return (
      <button
        type="button"
        className={classList('IdeationVote', this.attrs.wide && 'IdeationVote--wide Button', voted && 'IdeationVote--voted', this.attrs.className)}
        aria-pressed={voted ? 'true' : 'false'}
        aria-label={extractText(t(voted ? 'vote.remove' : 'vote.add', { count: votes }))}
        title={usable ? undefined : extractText(t('vote.closed'))}
        disabled={!usable}
        onclick={(e: MouseEvent) => {
          e.preventDefault();
          e.stopPropagation();
          toggleVote(discussion);
        }}
      >
        <Icon name="fas fa-chevron-up" className="IdeationVote-icon" />
        <span className="IdeationVote-count">{votes}</span>
        {this.attrs.wide ? <span className="IdeationVote-label">{t(voted ? 'vote.voted' : 'vote.vote')}</span> : null}
      </button>
    );
  }
}
