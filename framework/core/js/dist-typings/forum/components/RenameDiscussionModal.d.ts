/// <reference path="../../@types/translator-icu-rich.d.ts" />
/**
 * The 'RenameDiscussionModal' displays a modal dialog with an input to rename a discussion
 */
export default class RenameDiscussionModal extends Modal<import("../../common/components/Modal").IInternalModalAttrs> {
    constructor();
    oninit(vnode: any): void;
    discussion: any;
    currentTitle: any;
    newTitle: Stream<any> | undefined;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element;
    onsubmit(e: any): any;
}
import Modal from "../../common/components/Modal";
import Stream from "../../common/utils/Stream";
