export type GeneralIndexItem = {
    /**
     * The unique identifier for this index item.
     */
    id: string;
    /**
     * Optional: The tree path to this item, used for grouping in the search results.
     */
    tree?: string[];
    /**
     * The label to display in the search results.
     */
    label: string;
    /**
     * Optional: The description to display in the search results.
     */
    help?: string;
    /**
     * Optional: The URL to navigate to when this item is selected.
     * The default is to navigate to the extension page.
     */
    link?: string;
    /**
     * Optional: A callback that returns a boolean indicating whether this item should be visible in the search results.
     */
    visible?: () => boolean;
};
export type GeneralIndexData = Record<string, Record<'settings' | 'permissions', GeneralIndexItem[]>>;
export type GeneralIndexGroup = {
    label: string;
    icon: {
        name: string;
        [key: string]: any;
    };
    link: string;
};
export default class GeneralSearchIndex {
    protected currentId: string;
    protected data: GeneralIndexData;
    protected groups: Record<string, GeneralIndexGroup>;
    group(id: string, data: GeneralIndexGroup): this;
    for(id: string): this;
    add(type: 'settings' | 'permissions', items: GeneralIndexItem[]): void;
    getData(): GeneralIndexData;
    getGroup(id: string): null | GeneralIndexGroup;
}
