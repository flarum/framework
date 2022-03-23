import Tag from "../../common/models/Tag";
export default class TagListState {
    loadedIncludes: Set<unknown>;
    load(includes?: never[]): Promise<Tag[]>;
}
