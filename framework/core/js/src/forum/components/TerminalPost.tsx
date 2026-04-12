import app from '../app';
import Component, { ComponentAttrs } from '../../common/Component';
import humanTime from '../../common/helpers/humanTime';

import Icon from '../../common/components/Icon';

import type Discussion from '../../common/models/Discussion';

export interface ITerminalPostAttrs extends ComponentAttrs {
  discussion: Discussion;
  lastPost?: boolean;
}

/**
 * Displays information about a the first or last post in a discussion.
 */
export default class TerminalPost<CustomAttrs extends ITerminalPostAttrs = ITerminalPostAttrs> extends Component<CustomAttrs> {
  view() {
    const discussion = this.attrs.discussion;
    const lastPost = this.attrs.lastPost && discussion.replyCount();

    const user = discussion[lastPost ? 'lastPostedUser' : 'user']();
    const time = discussion[lastPost ? 'lastPostedAt' : 'createdAt']();

    return (
      <span>
        {!!lastPost && <Icon name={'fas fa-reply'} />}{' '}
        {time &&
          app.translator.trans('core.forum.discussion_list.' + (lastPost ? 'replied' : 'started') + '_text', {
            user,
            ago: humanTime(time),
          })}
      </span>
    );
  }
}
