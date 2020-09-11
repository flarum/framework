import Component from '../../common/Component';
import humanTime from '../../common/utils/humanTime';
import extractText from '../../common/utils/extractText';

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

    this.shouldUpdateTooltip = false;
    this.oldEditedInfo = null;
  }

  view() {
    const post = this.attrs.post;
    const editedUser = post.editedUser();
    const editedInfo = extractText(app.translator.trans('core.forum.post.edited_tooltip', { user: editedUser, ago: humanTime(post.editedAt()) }));
    if (editedInfo !== this.oldEditedInfo) {
      this.shouldUpdateTooltip = true;
      this.oldEditedInfo = editedInfo;
    }

    return (
      <span className="PostEdited" title={editedInfo}>
        {app.translator.trans('core.forum.post.edited_text')}
      </span>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.rebuildTooltip();
  }

  onupdate() {
    this.rebuildTooltip();
  }

  rebuildTooltip() {
    if (this.shouldUpdateTooltip) {
      this.$().tooltip('destroy').tooltip();
      this.shouldUpdateTooltip = false;
    }
  }
}
