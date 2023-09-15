import type Tag from '../../common/models/Tag';
export default class TagListState {
    loadedIncludes?: Set<string>;
    load(includes?: string[]): Promise<Tag[]>;
    query(includes?: string[]): Promise<Tag[]>;
}
