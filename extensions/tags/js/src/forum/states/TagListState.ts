import app from "flarum/forum/app";
import type Tag from "../../common/models/Tag";

export default class TagListState {
  loadedIncludes = new Set();

  async load(includes: string[] = []): Promise<Tag[]> {
    const unloadedIncludes = includes.filter(
      (include) => !this.loadedIncludes.has(include)
    );

    if (unloadedIncludes.length === 0) {
      return Promise.resolve(app.store.all<Tag>("tags"));
    }

    return app.store
      .find<Tag[]>("tags", { include: unloadedIncludes.join(",") })
      .then((val) => {
        unloadedIncludes.forEach((include) => this.loadedIncludes.add(include));
        return val;
      });
  }
}
