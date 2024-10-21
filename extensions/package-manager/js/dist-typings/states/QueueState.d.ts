import Task from '../models/Task';
import { ApiQueryParamsPlural } from 'flarum/common/Store';
export default class QueueState {
    private polling;
    private tasks;
    private limit;
    private offset;
    private total;
    private loading;
    load(params?: ApiQueryParamsPlural, actionTaken?: boolean): Promise<Task[]>;
    isLoading(): boolean;
    getItems(): Task[] | null;
    getTotalItems(): number;
    getTotalPages(): number;
    pageNumber(): number;
    getPerPage(): number;
    hasPrev(): boolean;
    hasNext(): boolean;
    prev(): void;
    next(): void;
    goto(page: number): void;
    pollQueue(actionTaken?: boolean): void;
    hasPending(): boolean;
}
