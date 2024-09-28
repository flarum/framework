export declare type GeneralIndexItem = {
    id: string;
    tree?: string[];
    label: string;
    help?: string;
    link?: string;
    visible?: () => boolean;
};
export declare type GeneralIndexData = Record<string, Record<'settings' | 'permissions', GeneralIndexItem[]>>;
export declare type GeneralIndexGroup = {
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
