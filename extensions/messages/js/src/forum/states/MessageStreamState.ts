import PaginatedListState, { PaginatedListParams } from 'flarum/common/states/PaginatedListState';
import DialogMessage from '../../common/models/DialogMessage';

export interface MessageStreamParams extends PaginatedListParams {
  //
}

export default class MessageStreamState<P extends MessageStreamParams = MessageStreamParams> extends PaginatedListState<DialogMessage, P> {
  constructor(params: P, page: number = 1) {
    super(params, page, null);
  }

  get type(): string {
    return 'dialog-messages';
  }

  public getAllItems(): DialogMessage[] {
    return super.getAllItems();
  }
}
