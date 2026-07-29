/// <reference types="mithril" />
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type User from 'flarum/common/models/User';
export interface IUserAuditModalAttrs extends IInternalModalAttrs {
    user: User;
}
export default class UserAuditModal<CustomAttrs extends IUserAuditModalAttrs = IUserAuditModalAttrs> extends Modal<CustomAttrs> {
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
}
