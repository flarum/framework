/// <reference types="flarum/@types/translator-icu-rich" />
/**
 * The `EditTagModal` component shows a modal dialog which allows the user
 * to create or edit a tag.
 */
export default class EditTagModal extends Modal<import("flarum/common/components/Modal").IInternalModalAttrs> {
    constructor();
    oninit(vnode: any): void;
    tag: any;
    name: Stream<any> | undefined;
    slug: Stream<any> | undefined;
    description: Stream<any> | undefined;
    color: Stream<any> | undefined;
    icon: Stream<any> | undefined;
    isHidden: Stream<any> | undefined;
    primary: Stream<any> | undefined;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray | import("mithril").Vnode<any, any>;
    content(): JSX.Element;
    fields(): ItemList<any>;
    submitData(): {
        name: any;
        slug: any;
        description: any;
        color: any;
        icon: any;
        isHidden: any;
        primary: any;
    };
    onsubmit(e: any): void;
    delete(): void;
}
import Modal from "flarum/common/components/Modal";
import Stream from "flarum/common/utils/Stream";
import ItemList from "flarum/common/utils/ItemList";
