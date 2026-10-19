/// <reference types="mithril" />
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type DeckState from '../states/DeckState';
export interface IDeckRowSplitAttrs extends ComponentAttrs {
    deck: DeckState;
}
/**
 * The divider between the two rows: drag it, or focus it and use the arrow
 * keys, to share the height between them. Double-click (or Home) evens it.
 */
export default class DeckRowSplit<CustomAttrs extends IDeckRowSplitAttrs = IDeckRowSplitAttrs> extends Component<CustomAttrs> {
    protected dragging: boolean;
    view(): JSX.Element;
    protected start(e: PointerEvent): void;
    protected move(e: PointerEvent & {
        redraw?: boolean;
    }): void;
    protected end(): void;
    protected key(e: KeyboardEvent & {
        redraw?: boolean;
    }): void;
}
