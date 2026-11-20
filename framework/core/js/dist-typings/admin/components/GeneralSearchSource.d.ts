import type Mithril from 'mithril';
import type { GlobalSearchSource } from './GlobalSearch';
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
export default class GeneralSearchSource implements GlobalSearchSource {
    protected results: Map<string, GeneralSearchResult[]>;
    resource: string;
    /**
     * Admin settings search runs in the browser and sends no requests, so a
     * raised forum minimum does not apply to it. It searches from one character
     * in CJK mode, where a single character is often a whole word, and follows
     * a lowered forum minimum otherwise.
     */
    minSearchLength(): number;
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
