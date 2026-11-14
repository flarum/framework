import app from '../../app';
import { KeyValueGambit } from '../IGambit';

export default class SourceGambit extends KeyValueGambit {
  key(): string {
    return app.translator.trans('core.lib.gambits.posts.source.key', {}, true);
  }

  canonicalKey(): string {
    return 'source';
  }

  hint(): string {
    return app.translator.trans('core.lib.gambits.posts.source.hint', {}, true);
  }

  filterKey(): string {
    return 'source';
  }

  enabled(): boolean {
    return !!app.forum?.attribute('showPostSource');
  }
}
