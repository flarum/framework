/// <reference types="mithril" />
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Discussion from 'flarum/common/models/Discussion';
export interface IDiscussionAuditModalAttrs extends IInternalModalAttrs {
    discussion: Discussion;
}
export default class DiscussionAuditModal<CustomAttrs extends IDiscussionAuditModalAttrs = IDiscussionAuditModalAttrs> extends Modal<CustomAttrs> {
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
}
