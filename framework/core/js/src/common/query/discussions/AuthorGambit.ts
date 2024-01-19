import app from '../../app';
import { KeyValueGambit } from '../IGambit';

export default class AuthorGambit extends KeyValueGambit {
  key(): string {
    return app.translator.trans('core.lib.gambits.discussions.author.key', {}, true);
  }

  hint(): string {
    return app.translator.trans('core.lib.gambits.discussions.author.hint', {}, true);
  }

  filterKey(): string {
    return 'author';
  }
}
