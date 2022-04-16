import type Tag from "../../common/models/Tag";
export default class TagListState {
    loadedIncludes: Set<unknown>;
    load(includes?: string[]): Promise<Tag[]>;
}
