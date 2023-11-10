/// <reference types="flarum/@types/translator-icu-rich" />
export default class FlagPostModal {
    oninit(vnode: any): void;
    success: boolean | undefined;
    reason: Stream<string> | undefined;
    reasonDetail: Stream<string> | undefined;
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element;
    flagReasons(): ItemList<any>;
    onsubmit(e: any): void;
    loading: boolean | undefined;
}
import Stream from "flarum/common/utils/Stream";
import ItemList from "flarum/common/utils/ItemList";
