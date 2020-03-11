import Component from '../../common/Component';
import { PostProp } from '../../common/concerns/ComponentProps';
import humanTime from '../../common/utils/humanTime';

/**
 * The `PostEdited` component displays information about when and by whom a post
 * was edited.
 */
export default class PostEdited extends Component<PostProp> {
    shouldUpdateTooltip = false;
    oldEditedInfo?: string;

    view() {
        const post = this.props.post;
        const editedUser = post.editedUser();
        const editedInfo = app.translator.transText('core.forum.post.edited_tooltip', { user: editedUser, ago: humanTime(post.editedAt()) });

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

        this.$().tooltip();
    }

    onupdate(vnode) {
        super.onupdate(vnode);

        if (this.shouldUpdateTooltip) {
            this.$()
                .tooltip('destroy')
                .tooltip();
            this.shouldUpdateTooltip = false;
        }
    }
}
