import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Icon from 'flarum/common/components/Icon';
import Link from 'flarum/common/components/Link';
import type Discussion from 'flarum/common/models/Discussion';
import VoteButton from './VoteButton';
import GithubLinks from './GithubLinks';
import { ICONS, statusLabel } from '../status';

export interface IdeationPanelAttrs extends ComponentAttrs {
  discussion: Discussion;
}

/** Where this idea is and the vote, at the top of its discussion's sidebar. */
export default class IdeationPanel extends Component<IdeationPanelAttrs> {
  view() {
    const discussion = this.attrs.discussion;
    const status = discussion.ideationStatus() || '';
    const mergedInto = discussion.ideationMergedIntoId();

    return (
      <div className="IdeationPanel">
        <Link className={'IdeationPanel-status IdeationStatus--' + status} href={app.route('ideation')}>
          <Icon name={ICONS[status] || 'fas fa-map-signs'} />
          <span>{statusLabel(status)}</span>
        </Link>
        {status === 'merged' && mergedInto ? (
          <Link className="Button Button--block IdeationPanel-merged" href={app.route('discussion', { id: String(mergedInto) })}>
            {app.translator.trans('flarum-ideation.forum.panel.go_to_merged')}
          </Link>
        ) : (
          <VoteButton discussion={discussion} wide />
        )}
        <GithubLinks links={discussion.ideationGithub() || []} />
      </div>
    );
  }
}
