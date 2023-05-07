/// <reference path="../../@types/translator-icu-rich.d.ts" />
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Stream from '../../common/utils/Stream';
import type Mithril from 'mithril';
import RequestError from '../../common/utils/RequestError';
import ItemList from '../../common/utils/ItemList';
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
    fields(): ItemList<Mithril.Children>;
    onsubmit(e: SubmitEvent): void;
    requestAttributes(): {
        email: string;
    };
    onerror(error: RequestError): void;
}
