import PaginatedListState, { PaginatedListParams } from 'flarum/common/states/PaginatedListState';
import DialogMessage from '../../common/models/DialogMessage';
export interface MessageStreamParams extends PaginatedListParams {
}
export default class MessageStreamState<P extends MessageStreamParams = MessageStreamParams> extends PaginatedListState<DialogMessage, P> {
    constructor(params: P, page?: number);
    get type(): string;
    getAllItems(): DialogMessage[];
}
