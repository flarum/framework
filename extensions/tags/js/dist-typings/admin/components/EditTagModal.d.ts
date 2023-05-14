/// <reference types="flarum/@types/translator-icu-rich" />
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import type Tag from '../../common/models/Tag';
export interface EditTagModalAttrs extends IInternalModalAttrs {
    primary?: boolean;
    model?: Tag;
}
/**
 * The `EditTagModal` component shows a modal dialog which allows the user
 * to create or edit a tag.
 */
export default class EditTagModal extends Modal<EditTagModalAttrs> {
    tag: Tag;
    name: Stream<string>;
    slug: Stream<string>;
    description: Stream<string>;
    color: Stream<string>;
    icon: Stream<string>;
    isHidden: Stream<boolean>;
    primary: Stream<boolean>;
    oninit(vnode: Mithril.Vnode<EditTagModalAttrs, this>): void;
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray | Mithril.Vnode<import("flarum/common/Component").ComponentAttrs, any>;
    content(): JSX.Element;
    fields(): ItemList<unknown>;
    submitData(): {
        name: string;
        slug: string;
        description: string;
        color: string;
        icon: string;
        isHidden: boolean;
        primary: boolean;
    };
    onsubmit(e: SubmitEvent): void;
    delete(): void;
}
