/// <reference path="../../../src/common/translator-icu-rich.d.ts" />
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import ItemList from '../../common/utils/ItemList';
import Stream from '../../common/utils/Stream';
import type Mithril from 'mithril';
import RequestError from '../../common/utils/RequestError';
export interface ILoginModalAttrs extends IInternalModalAttrs {
    identification?: string;
    password?: string;
    remember?: boolean;
}
export default class LogInModal<CustomAttrs extends ILoginModalAttrs = ILoginModalAttrs> extends Modal<CustomAttrs> {
    /**
     * The value of the identification input.
     */
    identification: Stream<string>;
    /**
     * The value of the password input.
     */
    password: Stream<string>;
    /**
     * The value of the remember me input.
     */
    remember: Stream<boolean>;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element[];
    body(): JSX.Element[];
    fields(): ItemList<unknown>;
    footer(): (string | JSX.Element)[];
    /**
     * Open the forgot password modal, prefilling it with an email if the user has
     * entered one.
     */
    forgotPassword(): void;
    /**
     * Open the sign up modal, prefilling it with an email/username/password if
     * the user has entered one.
     */
    signUp(): void;
    onready(): void;
    onsubmit(e: SubmitEvent): void;
    onerror(error: RequestError): void;
}
