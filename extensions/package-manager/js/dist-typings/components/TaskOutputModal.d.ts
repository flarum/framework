/// <reference types="mithril" />
/// <reference types="@flarum/core/dist-typings/@types/translator-icu-rich" />
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Task from '../models/Task';
interface TaskOutputModalAttrs extends IInternalModalAttrs {
    task: Task;
}
export default class TaskOutputModal<CustomAttrs extends TaskOutputModalAttrs = TaskOutputModalAttrs> extends Modal<CustomAttrs> {
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element;
}
export {};
