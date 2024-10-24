/// <reference types="mithril" />
import Component, { type ComponentAttrs } from '../../common/Component';
import type Discussion from '../../common/models/Discussion';
import type Model from '../../common/Model';
export interface IReplyPlaceholderAttrs extends ComponentAttrs {
    discussion: Discussion | Model;
    onclick?: () => void;
    composingReply?: () => boolean;
}
/**
 * The `ReplyPlaceholder` component displays a placeholder for a reply, which,
 * when clicked, opens the reply composer.
 */
export default class ReplyPlaceholder<CustomAttrs extends IReplyPlaceholderAttrs = IReplyPlaceholderAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
    anchorPreview(preview: () => void): void;
}
