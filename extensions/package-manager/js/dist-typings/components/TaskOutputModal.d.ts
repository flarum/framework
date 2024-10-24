/// <reference types="mithril" />
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Task from '../models/Task';
interface TaskOutputModalAttrs extends IInternalModalAttrs {
    task: Task;
}
export default class TaskOutputModal<CustomAttrs extends TaskOutputModalAttrs = TaskOutputModalAttrs> extends Modal<CustomAttrs> {
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
}
export {};
