/// <reference path="../../@types/translator-icu-rich.d.ts" />
import Modal, { IInternalModalAttrs } from './Modal';
import ItemList from '../utils/ItemList';
import Stream from '../utils/Stream';
import type Mithril from 'mithril';
import type User from '../models/User';
import type { SaveAttributes } from '../Model';
export interface IEditUserModalAttrs extends IInternalModalAttrs {
    user: User;
}
export default class EditUserModal<CustomAttrs extends IEditUserModalAttrs = IEditUserModalAttrs> extends Modal<CustomAttrs> {
    protected username: Stream<string>;
    protected email: Stream<string>;
    protected isEmailConfirmed: Stream<boolean>;
    protected setPassword: Stream<boolean>;
    protected password: Stream<string>;
    protected groups: Record<string, Stream<boolean>>;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element;
    fields(): ItemList<unknown>;
    activate(): void;
    data(): SaveAttributes;
    onsubmit(e: SubmitEvent): void;
    nonAdminEditingAdmin(): boolean;
    /**
     * @internal
     */
    protected userIsAdmin(user: User | null): boolean;
}
