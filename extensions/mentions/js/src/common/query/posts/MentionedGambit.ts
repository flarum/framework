import { KeyValueGambit } from 'flarum/common/query/IGambit';
import app from 'flarum/common/app';

export default class MentionedGambit extends KeyValueGambit {
  key(): string {
    return app.translator.trans('flarum-mentions.lib.gambits.posts.mentioned.key', {}, true);
  }

  hint(): string {
    return app.translator.trans('flarum-mentions.lib.gambits.posts.mentioned.hint', {}, true);
  }

  filterKey(): string {
    return 'mentioned';
  }
}
