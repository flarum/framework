/// <reference types="flarum/@types/translator-icu-rich" />
export default class FlagPostModal extends Modal<import("flarum/common/components/Modal").IInternalModalAttrs, undefined> {
    constructor();
    oninit(vnode: any): void;
    success: boolean | undefined;
    reason: Stream<string> | undefined;
    reasonDetail: Stream<string> | undefined;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element;
    flagReasons(): ItemList<any>;
    onsubmit(e: any): void;
}
import Modal from "flarum/common/components/Modal";
import Stream from "flarum/common/utils/Stream";
import ItemList from "flarum/common/utils/ItemList";
