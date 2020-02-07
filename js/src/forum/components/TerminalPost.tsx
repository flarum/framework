import Component from '../../common/Component';
import humanTime from '../../common/helpers/humanTime';
import icon from '../../common/helpers/icon';
import Post from '../../common/models/Post';
import { DiscussionProp } from '../../common/concerns/ComponentProps';

export interface TerminalPostProps extends DiscussionProp {
    lastPost: Post;
}

/**
 * Displays information about a the first or last post in a discussion.
 */
export default class TerminalPost<T extends TerminalPostProps = TerminalPostProps> extends Component<T> {
    view() {
        const discussion = this.props.discussion;
        const lastPost = this.props.lastPost && discussion.replyCount();

        const user = discussion[lastPost ? 'lastPostedUser' : 'user']();
        const time = discussion[lastPost ? 'lastPostedAt' : 'createdAt']();

        return (
            <span>
                {lastPost ? icon('fas fa-reply') : ''}{' '}
                {app.translator.trans(`core.forum.discussion_list.${lastPost ? 'replied' : 'started'}_text`, {
                    user,
                    ago: humanTime(time),
                })}
            </span>
        );
    }
}
