/// <reference path="../../@types/translator-icu-rich.d.ts" />
/**
 * The `ChangePasswordModal` component shows a modal dialog which allows the
 * user to send themself a password reset email.
 */
export default class ChangePasswordModal extends Modal<import("../../common/components/Modal").IInternalModalAttrs> {
    constructor();
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element;
    onsubmit(e: any): void;
}
import Modal from "../../common/components/Modal";
