/// <reference types="flarum/@types/translator-icu-rich" />
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Group from '../../common/models/Group';
import ItemList from '../../common/utils/ItemList';
import Stream from '../../common/utils/Stream';
import Mithril from 'mithril';
export interface IEditGroupModalAttrs extends IInternalModalAttrs {
    group?: Group;
}
/**
 * The `EditGroupModal` component shows a modal dialog which allows the user
 * to create or edit a group.
 */
export default class EditGroupModal<CustomAttrs extends IEditGroupModalAttrs = IEditGroupModalAttrs> extends Modal<CustomAttrs> {
    group: Group;
    nameSingular: Stream<string>;
    namePlural: Stream<string>;
    icon: Stream<string>;
    color: Stream<string>;
    isHidden: Stream<boolean>;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): (Mithril.Vnode<{}, {}> | import("@askvortsov/rich-icu-message-formatter").NestedStringArray)[];
    content(): JSX.Element;
    fields(): ItemList<unknown>;
    submitData(): {
        nameSingular: string;
        namePlural: string;
        color: string;
        icon: string;
        isHidden: boolean;
    };
    onsubmit(e: SubmitEvent): void;
    deleteGroup(): void;
}
