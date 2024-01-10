import Task from '../models/Task';
import { ApiQueryParamsPlural } from 'flarum/common/Store';
export default class QueueState {
    private polling;
    private tasks;
    private limit;
    private offset;
    private total;
    load(params?: ApiQueryParamsPlural, actionTaken?: boolean): Promise<Task[]>;
    getItems(): Task[] | null;
    getTotalPages(): number;
    pageNumber(): number;
    hasPrev(): boolean;
    hasNext(): boolean;
    prev(): void;
    next(): void;
    pollQueue(actionTaken?: boolean): void;
    hasPending(): boolean;
}
