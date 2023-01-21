import app from '../../forum/app';
import EventPost from './EventPost';
import extractText from '../../common/utils/extractText';
import Tooltip from '../../common/components/Tooltip';

/**
 * The `DiscussionRenamedPost` component displays a discussion event post
 * indicating that the discussion has been renamed.
 *
 * ### Attrs
 *
 * - All of the attrs for EventPost
 */
export default class DiscussionRenamedPost extends EventPost {
  icon() {
    return 'fas fa-pencil-alt';
  }

  description(data) {
    const renamed = app.translator.trans('core.forum.post_stream.discussion_renamed_text', data);

    return <span>{renamed}</span>;
  }

  descriptionData() {
    const post = this.attrs.post;
    const oldTitle = post.content()[0];
    const newTitle = post.content()[1];

    return {
      new: (
        <Tooltip text={extractText(app.translator.trans('core.forum.post_stream.discussion_renamed_old_tooltip', { old: oldTitle }))}>
          <strong className="DiscussionRenamedPost-new">{newTitle}</strong>
        </Tooltip>
      ),
    };
  }
}
