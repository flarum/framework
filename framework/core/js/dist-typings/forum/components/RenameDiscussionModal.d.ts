/// <reference path="../../@types/translator-icu-rich.d.ts" />
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Stream from '../../common/utils/Stream';
import Mithril from 'mithril';
import Discussion from '../../common/models/Discussion';
export interface IRenameDiscussionModalAttrs extends IInternalModalAttrs {
    discussion: Discussion;
    currentTitle: string;
}
/**
 * The 'RenameDiscussionModal' displays a modal dialog with an input to rename a discussion
 */
export default class RenameDiscussionModal<CustomAttrs extends IRenameDiscussionModalAttrs = IRenameDiscussionModalAttrs> extends Modal<CustomAttrs> {
    discussion: Discussion;
    currentTitle: string;
    newTitle: Stream<string>;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element;
    onsubmit(e: SubmitEvent): Promise<void> | void;
}
