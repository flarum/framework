import app from 'flarum/common/app';
import type Tag from '../../common/models/Tag';

export default class TagListState {
  loadedIncludes?: Set<string>;

  async load(includes: string[] = []): Promise<Tag[]> {
    if (!this.loadedIncludes) {
      return this.query(includes);
    }

    const unloadedIncludes = includes.filter((include) => !this.loadedIncludes!.has(include));

    if (unloadedIncludes.length === 0) {
      return Promise.resolve(app.store.all<Tag>('tags'));
    }

    return this.query(unloadedIncludes);
  }

  async query(includes: string[] = []): Promise<Tag[]> {
    this.loadedIncludes ??= new Set();

    return app.store.find<Tag[]>('tags', { include: includes.join(',') }).then((val) => {
      includes.forEach((include) => this.loadedIncludes!.add(include));
      return val;
    });
  }
}
