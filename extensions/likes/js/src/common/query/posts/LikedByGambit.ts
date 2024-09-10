import { KeyValueGambit } from 'flarum/common/query/IGambit';
import app from 'flarum/common/app';

export default class LikedByGambit extends KeyValueGambit {
  key(): string {
    return app.translator.trans('flarum-likes.lib.gambits.posts.likedBy.key', {}, true);
  }

  hint(): string {
    return app.translator.trans('flarum-likes.lib.gambits.posts.likedBy.hint', {}, true);
  }

  filterKey(): string {
    return 'likedBy';
  }
}
