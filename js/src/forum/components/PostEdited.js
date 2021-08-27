import app from '../../forum/app';
import Component from '../../common/Component';
import humanTime from '../../common/utils/humanTime';
import Tooltip from '../../common/components/Tooltip';

/**
 * The `PostEdited` component displays information about when and by whom a post
 * was edited.
 *
 * ### Attrs
 *
 * - `post`
 */
export default class PostEdited extends Component {
  oninit(vnode) {
    super.oninit(vnode);
  }

  view() {
    const post = this.attrs.post;
    const editedUser = post.editedUser();
    const editedInfo = app.translator.trans('core.forum.post.edited_tooltip', { user: editedUser, ago: humanTime(post.editedAt()) });

    return (
      <Tooltip text={editedInfo}>
        <span class="PostEdited">{app.translator.trans('core.forum.post.edited_text')}</span>
      </Tooltip>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);
  }
}
