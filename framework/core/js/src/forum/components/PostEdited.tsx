import app from '../app';
import Component, { ComponentAttrs } from '../../common/Component';
import humanTime from '../../common/utils/humanTime';
import Tooltip from '../../common/components/Tooltip';

import type Post from '../../common/models/Post';

export interface IPostEditedAttrs extends ComponentAttrs {
  post: Post;
}

/**
 * The `PostEdited` component displays information about when and by whom a post
 * was edited.
 */
export default class PostEdited<CustomAttrs extends IPostEditedAttrs = IPostEditedAttrs> extends Component<CustomAttrs> {
  view() {
    const post = this.attrs.post;
    const editedUser = post.editedUser();
    const editedInfo = app.translator.trans('core.forum.post.edited_tooltip', { user: editedUser, ago: humanTime(post.editedAt()) });

    return (
      <Tooltip text={editedInfo}>
        <span className="PostEdited">{app.translator.trans('core.forum.post.edited_text')}</span>
      </Tooltip>
    );
  }
}
