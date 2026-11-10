import Component, { ComponentAttrs } from '../../common/Component';
import type Discussion from '../../common/models/Discussion';
export interface ITerminalPostAttrs extends ComponentAttrs {
    discussion: Discussion;
    lastPost?: boolean;
}
/**
 * Displays information about a the first or last post in a discussion.
 */
export default class TerminalPost<CustomAttrs extends ITerminalPostAttrs = ITerminalPostAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
}
