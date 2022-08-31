import Task from '../models/Task';
import { ApiQueryParamsPlural } from 'flarum/common/Store';
export default class QueueState {
    private tasks;
    private limit;
    private offset;
    private total;
    load(params?: ApiQueryParamsPlural): Promise<import("flarum/common/Store").ApiResponsePlural<Task>>;
    getItems(): Task[] | null;
    getTotalPages(): number;
    pageNumber(): number;
    hasPrev(): boolean;
    hasNext(): boolean;
    prev(): void;
    next(): void;
}
