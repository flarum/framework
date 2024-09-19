import app from '../../app';
import { KeyValueGambit } from '../IGambit';

export default class DiscussionGambit extends KeyValueGambit {
  key(): string {
    return app.translator.trans('core.lib.gambits.posts.discussion.key', {}, true);
  }

  hint(): string {
    return app.translator.trans('core.lib.gambits.posts.discussion.hint', {}, true);
  }

  filterKey(): string {
    return 'discussion';
  }
}
