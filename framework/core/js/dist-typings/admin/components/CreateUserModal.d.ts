/// <reference path="../../@types/translator-icu-rich.d.ts" />
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import ItemList from '../../common/utils/ItemList';
import Stream from '../../common/utils/Stream';
import type Mithril from 'mithril';
export interface ICreateUserModalAttrs extends IInternalModalAttrs {
    username?: string;
    email?: string;
    password?: string;
    token?: string;
    provided?: string[];
}
export declare type SignupBody = {
    username: string;
    email: string;
    isEmailConfirmed: boolean;
    password: string;
};
export default class CreateUserModal<CustomAttrs extends ICreateUserModalAttrs = ICreateUserModalAttrs> extends Modal<CustomAttrs> {
    /**
     * The value of the username input.
     */
    username: Stream<string>;
    /**
     * The value of the email input.
     */
    email: Stream<string>;
    /**
     * The value of the password input.
     */
    password: Stream<string | null>;
    /**
     * Whether email confirmation is required after signing in.
     */
    requireEmailConfirmation: Stream<boolean>;
    /**
     * Keeps the modal open after the user is created to facilitate creating
     * multiple users at once.
     */
    bulkAdd: Stream<boolean>;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element;
    body(): JSX.Element;
    fields(): ItemList<unknown>;
    onready(): void;
    onsubmit(e?: SubmitEvent | null): void;
    /**
     * Get the data that should be submitted in the sign-up request.
     */
    submitData(): SignupBody;
    resetData(): void;
}
