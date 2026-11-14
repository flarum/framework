import Component, { ComponentAttrs } from '../../common/Component';
import type Post from '../../common/models/Post';
export interface IPostEditedAttrs extends ComponentAttrs {
    post: Post;
}
/**
 * The `PostEdited` component displays information about when and by whom a post
 * was edited.
 */
export default class PostEdited<CustomAttrs extends IPostEditedAttrs = IPostEditedAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
}
