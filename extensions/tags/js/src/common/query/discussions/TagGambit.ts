import app from 'flarum/common/app';
import { KeyValueGambit } from 'flarum/common/query/IGambit';
import type Tag from '../../models/Tag';

export default class TagGambit extends KeyValueGambit {
  predicates = true;

  static RETRY_COOLDOWN_MS = 10000;
  static loadingTags = false;
  static loadedTags = false;
  static lastLoadFailedAt = 0;

  key(): string {
    return app.translator.trans('flarum-tags.lib.gambits.discussions.tag.key', {}, true);
  }

  canonicalKey(): string {
    return 'tag';
  }

  hint(): string {
    return app.translator.trans('flarum-tags.lib.gambits.discussions.tag.hint', {}, true);
  }

  filterKey(): string {
    return 'tag';
  }

  values() {
    const hasRecentlyFailed = TagGambit.lastLoadFailedAt && Date.now() - TagGambit.lastLoadFailedAt < TagGambit.RETRY_COOLDOWN_MS;

    if (app.tagList && !TagGambit.loadedTags && !TagGambit.loadingTags && !hasRecentlyFailed) {
      TagGambit.loadingTags = true;

      app.tagList
        .load(['parent', 'children'])
        .then(() => {
          TagGambit.loadedTags = true;
          m.redraw();
        })
        .catch(() => {
          TagGambit.lastLoadFailedAt = Date.now();
        })
        .finally(() => {
          TagGambit.loadingTags = false;
        });
    }

    return (app.store.all<Tag>('tags') || [])
      .map((tag) => ({
        value: tag.slug(),
        label: tag.name(),
      }))
      .filter((opt) => !!opt.value);
  }

  gambitValueToFilterValue(value: string): string[] {
    return [value];
  }

  fromFilter(value: any, negate: boolean): string {
    let gambits = [];

    if (Array.isArray(value)) {
      gambits = value.map((value) => this.fromFilter(value.toString(), negate));
    } else {
      return `${negate ? '-' : ''}${this.key()}:${this.filterValueToGambitValue(value)}`;
    }

    return gambits.join(' ');
  }

  filterValueToGambitValue(value: string): string {
    return value;
  }
}
