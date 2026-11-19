import ComposerBody, { type IComposerBodyAttrs } from 'flarum/forum/components/ComposerBody';
import type DialogMessage from '../../common/models/DialogMessage';
export interface IEditMessageComposerAttrs extends IComposerBodyAttrs {
    message: DialogMessage;
}
/**
 * Edits a message in the composer, starting from what it says now; the
 * message shows what is being written in its place meanwhile.
 */
export default class EditMessageComposer<CustomAttrs extends IEditMessageComposerAttrs = IEditMessageComposerAttrs> extends ComposerBody<CustomAttrs> {
    static focusOnSelector: () => string;
    static initAttrs(attrs: IEditMessageComposerAttrs): void;
    headerItems(): import("flarum/common/utils/ItemList").default<import("mithril").Children>;
    data(): Record<string, unknown>;
    onsubmit(): void;
}
