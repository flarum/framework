import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import type TypingState from '../states/TypingState';
export interface TypingIndicatorAttrs extends ComponentAttrs {
    state: TypingState;
}
/**
 * Renders the "X, Y and Z are typing" indicator for a discussion.
 *
 * The component is purely presentational: it is given a {@link TypingState}
 * (which owns the currently-typing users, fed from the realtime socket) and
 * renders its active set. Because it holds no socket logic, it can be placed
 * anywhere — added to the PostStream `endItems` list by default, but also
 * importable and rendered standalone wherever a theme or extension keeps a
 * TypingState.
 */
export default class TypingIndicator extends Component<TypingIndicatorAttrs> {
    view(): Mithril.Children;
}
