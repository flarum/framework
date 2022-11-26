/// <reference path="../../@types/translator-icu-rich.d.ts" />
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Stream from '../../common/utils/Stream';
import Mithril from 'mithril';
import RequestError from '../../common/utils/RequestError';
import ItemList from '../../common/utils/ItemList';
export interface IForgotPasswordModalAttrs extends IInternalModalAttrs {
    email?: string;
}
/**
 * The `ForgotPasswordModal` component displays a modal which allows the user to
 * enter their email address and request a link to reset their password.
 */
export default class ForgotPasswordModal<CustomAttrs extends IForgotPasswordModalAttrs = IForgotPasswordModalAttrs> extends Modal<CustomAttrs> {
    /**
     * The value of the email input.
     */
    email: Stream<string>;
    success: boolean;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element;
    fields(): ItemList<unknown>;
    onsubmit(e: SubmitEvent): void;
    requestParams(): Record<string, unknown>;
    onerror(error: RequestError): void;
}
