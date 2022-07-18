/// <reference types="flarum/@types/translator-icu-rich" />
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Stream from '../../common/utils/Stream';
import Mithril from 'mithril';
import RequestError from '../../common/utils/RequestError';
/**
 * The `ChangeEmailModal` component shows a modal dialog which allows the user
 * to change their email address.
 */
export default class ChangeEmailModal<CustomAttrs extends IInternalModalAttrs = IInternalModalAttrs> extends Modal<CustomAttrs> {
    /**
     * The value of the email input.
     */
    email: Stream<string>;
    /**
     * The value of the password input.
     */
    password: Stream<string>;
    /**
     * Whether or not the email has been changed successfully.
     */
    success: boolean;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element;
    onsubmit(e: SubmitEvent): void;
    onerror(error: RequestError): void;
}
