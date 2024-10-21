/// <reference types="mithril" />
import Component, { type ComponentAttrs } from '../../common/Component';
export interface ILoadingPostAttrs extends ComponentAttrs {
}
/**
 * The `LoadingPost` component shows a placeholder that looks like a post,
 * indicating that the post is loading.
 */
export default class LoadingPost<CustomAttrs extends ILoadingPostAttrs = ILoadingPostAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
}
