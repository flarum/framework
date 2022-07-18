/// <reference types="mithril" />
/// <reference types="flarum/@types/translator-icu-rich" />
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
/**
 * The `ChangePasswordModal` component shows a modal dialog which allows the
 * user to send themself a password reset email.
 */
export default class ChangePasswordModal<CustomAttrs extends IInternalModalAttrs = IInternalModalAttrs> extends Modal<CustomAttrs> {
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element;
    onsubmit(e: SubmitEvent): void;
}
