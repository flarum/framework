import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Icon from 'flarum/common/components/Icon';
import classList from 'flarum/common/utils/classList';
import type Discussion from 'flarum/common/models/Discussion';
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
    const voted = !!discussion.ideationVoted();
    const votes = discussion.ideationVotes() || 0;
    // A guest is offered the button (and asked to log in) only where voting is
    // open; the server's answer decides for everyone else.
    const usable = app.session.user ? !!discussion.canIdeationVote() : isOpen(discussion.ideationStatus());

    return (
      <Button
        className={classList('IdeationVote', this.attrs.wide && 'IdeationVote--wide Button', voted && 'IdeationVote--voted', this.attrs.className)}
        icon={<Icon name="fas fa-chevron-up" className="IdeationVote-icon" />}
        aria-pressed={voted ? 'true' : 'false'}
        aria-label={app.translator.trans(voted ? 'flarum-ideation.forum.vote.remove' : 'flarum-ideation.forum.vote.add', { count: votes }, true)}
        title={usable ? undefined : app.translator.trans('flarum-ideation.forum.vote.closed', {}, true)}
        disabled={!usable}
        onclick={(e: MouseEvent) => {
          e.preventDefault();
          e.stopPropagation();
          toggleVote(discussion);
        }}
      >
        <span className="IdeationVote-count">{votes}</span>
        {this.attrs.wide ? (
          <span className="IdeationVote-label">
            {app.translator.trans(voted ? 'flarum-ideation.forum.vote.voted' : 'flarum-ideation.forum.vote.vote')}
          </span>
        ) : null}
      </Button>
    );
  }
}
