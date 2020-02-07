import Component from '../../common/Component';
import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import DiscussionControls from '../utils/DiscussionControls';
import { DiscussionProp } from '../../common/concerns/ComponentProps';

/**
 * The `ReplyPlaceholder` component displays a placeholder for a reply, which,
 * when clicked, opens the reply composer.
 */
export default class ReplyPlaceholder<T extends DiscussionProp = DiscussionProp> extends Component<T> {
    view() {
        // TODO: add method & remove `false &&`
        if (false && app.composingReplyTo(this.props.discussion)) {
            return (
                <article className="Post CommentPost editing">
                    <header className="Post-header">
                        <div className="PostUser">
                            <h3>
                                {avatar(app.session.user, { className: 'PostUser-avatar' })}
                                {username(app.session.user)}
                            </h3>
                        </div>
                    </header>
                    <div className="Post-body" oncreate={this.oncreatePreview.bind(this)} />
                </article>
            );
        }

        const reply = () => DiscussionControls.replyAction.call(this.props.discussion, true);

        return (
            <article className="Post ReplyPlaceholder" onclick={reply}>
                <header className="Post-header">
                    {avatar(app.session.user, { className: 'PostUser-avatar' })} {app.translator.trans('core.forum.post_stream.reply_placeholder')}
                </header>
            </article>
        );
    }

    oncreatePreview(vnode) {
        // Every 50ms, if the composer content has changed, then update the post's
        // body with a preview.
        let preview;
        const updateInterval = setInterval(() => {
            // Since we're polling, the composer may have been closed in the meantime,
            // so we bail in that case.
            if (!app.composer.component) return;

            const content = app.composer.component.content();

            if (preview === content) return;

            preview = content;

            const anchorToBottom = $(window).scrollTop() + $(window).height() >= $(document).height();

            s9e.TextFormatter.preview(preview || '', vnode.dom);

            if (anchorToBottom) {
                $(window).scrollTop($(document).height());
            }
        }, 50);

        vnode.attrs.onunload = () => clearInterval(updateInterval);
    }
}
