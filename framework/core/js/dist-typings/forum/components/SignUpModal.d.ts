/// <reference path="../../@types/translator-icu-rich.d.ts" />
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import ItemList from '../../common/utils/ItemList';
import Stream from '../../common/utils/Stream';
import type Mithril from 'mithril';
export interface ISignupModalAttrs extends IInternalModalAttrs {
    username?: string;
    email?: string;
    password?: string;
    token?: string;
    provided?: string[];
}
export declare type SignupBody = {
    username: string;
    email: string;
} & ({
    token: string;
} | {
    password: string;
});
export default class SignUpModal<CustomAttrs extends ISignupModalAttrs = ISignupModalAttrs> extends Modal<CustomAttrs> {
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
    password: Stream<string>;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element[];
    isProvided(field: string): boolean;
    body(): (false | JSX.Element)[];
    fields(): ItemList<unknown>;
    footer(): JSX.Element[];
    /**
     * Open the log in modal, prefilling it with an email/username/password if
     * the user has entered one.
     */
    logIn(): void;
    onready(): void;
    onsubmit(e: SubmitEvent): void;
    /**
     * Get the data that should be submitted in the sign-up request.
     */
    submitData(): SignupBody;
}
