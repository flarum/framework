import app from '../../forum/app';
import Component from '../../common/Component';
import username from '../../common/helpers/username';
import DiscussionControls from '../utils/DiscussionControls';
import ComposerPostPreview from './ComposerPostPreview';
import listItems from '../../common/helpers/listItems';
import Avatar from '../../common/components/Avatar';

/**
 * The `ReplyPlaceholder` component displays a placeholder for a reply, which,
 * when clicked, opens the reply composer.
 *
 * ### Attrs
 *
 * - `discussion`
 */
export default class ReplyPlaceholder extends Component {
  view() {
    if (app.composer.composingReplyTo(this.attrs.discussion)) {
      return (
        <article className="Post CommentPost editing" aria-busy="true">
          <div className="Post-container">
            <div className="Post-side">
              <Avatar user={app.session.user} className="Post-avatar" />
            </div>
            <div className="Post-main">
              <header className="Post-header">
                <div className="PostUser">
                  <h3 className="PostUser-name">{username(app.session.user)}</h3>
                  <ul className="PostUser-badges badges badges--packed">{listItems(app.session.user.badges().toArray())}</ul>
                </div>
              </header>
              <div className="Post-body">
                <ComposerPostPreview className="Post-body" composer={app.composer} surround={this.anchorPreview.bind(this)} />
              </div>
            </div>
          </div>
        </article>
      );
    }

    const reply = () => {
      DiscussionControls.replyAction.call(this.attrs.discussion, true).catch(() => {});
    };

    return (
      <button className="Post ReplyPlaceholder" onclick={reply}>
        <div className="Post-container">
          <div className="Post-side">
            <Avatar user={app.session.user} className="Post-avatar" />
          </div>
          <div className="Post-main">
            <span className="Post-header">{app.translator.trans('core.forum.post_stream.reply_placeholder')}</span>
          </div>
        </div>
      </button>
    );
  }

  anchorPreview(preview) {
    const anchorToBottom = $(window).scrollTop() + $(window).height() >= $(document).height();

    preview();

    if (anchorToBottom) {
      $(window).scrollTop($(document).height());
    }
  }
}
