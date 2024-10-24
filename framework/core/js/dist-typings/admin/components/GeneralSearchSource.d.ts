import type Mithril from 'mithril';
import type { SearchSource } from './Search';
import { GeneralIndexData } from '../states/GeneralSearchIndex';
import { ExtensionConfig } from '../utils/AdminRegistry';
export declare class GeneralSearchResult {
    id: string;
    category: string;
    icon: {
        name: string;
        [key: string]: any;
    };
    tree: string[];
    link: string;
    help?: string | undefined;
    constructor(id: string, category: string, icon: {
        name: string;
        [key: string]: any;
    }, tree: string[], link: string, help?: string | undefined);
}
/**
 * Finds and displays settings, permissions and installed extensions (i.e. general search results) in the search dropdown.
 */
export default class GeneralSearchSource implements SearchSource {
    protected results: Map<string, GeneralSearchResult[]>;
    resource: string;
    title(): string;
    isCached(query: string): boolean;
    search(query: string, limit: number): Promise<void>;
    protected lookup(data: GeneralIndexData | {
        [key: string]: ExtensionConfig | undefined;
    }, query: string): GeneralSearchResult[];
    protected itemHasQuery(item: string, query: string): boolean;
    view(query: string): Array<Mithril.Vnode>;
    customGrouping(): boolean;
    fullPage(query: string): null;
    gotoItem(id: string): string | null;
}
