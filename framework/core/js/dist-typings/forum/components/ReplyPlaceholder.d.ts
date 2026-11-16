import Component, { type ComponentAttrs } from '../../common/Component';
import type Discussion from '../../common/models/Discussion';
import type Model from '../../common/Model';
export interface IReplyPlaceholderAttrs extends ComponentAttrs {
    discussion: Discussion | Model;
    onclick?: () => void;
    composingReply?: () => boolean;
    /** The heading level of the author's name while composing. Defaults to 2; pass 3 below a heading of its own. */
    headingLevel?: 2 | 3;
}
/**
 * The `ReplyPlaceholder` component displays a placeholder for a reply, which,
 * when clicked, opens the reply composer.
 */
export default class ReplyPlaceholder<CustomAttrs extends IReplyPlaceholderAttrs = IReplyPlaceholderAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
    anchorPreview(preview: () => void): void;
}
