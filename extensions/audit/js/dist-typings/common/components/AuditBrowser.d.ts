/// <reference types="mithril" />
import Component, { ComponentAttrs } from 'flarum/common/Component';
import { ApiResponsePlural } from 'flarum/common/Store';
import AuditLog from '../models/AuditLog';
interface AuditFilter {
    key: string;
    example: string;
    description: string | null;
    values: string[];
    extension: string | null;
}
interface AuditBrowserAttrs extends ComponentAttrs {
    baseQ?: string;
}
export default class AuditBrowser extends Component<AuditBrowserAttrs> {
    q: string;
    loading: boolean;
    moreResults: boolean;
    logs: AuditLog[];
    showHelp: boolean;
    caret: number;
    autocompleteFocused: boolean;
    oninit(vnode: any): void;
    view(): JSX.Element;
    /**
     * The whitespace-delimited token currently under the caret.
     */
    activeToken(): {
        start: number;
        end: number;
        text: string;
    };
    /**
     * Suggestions for the active token, when it's an `action:` or `client:` filter being typed.
     * Returns groups of values (by extension for actions, a single group for client), already
     * filtered by what the user has typed after the colon.
     */
    autocompleteGroups(): {
        extension: string | null;
        key: string;
        values: string[];
    }[] | null;
    autocomplete(): JSX.Element | null;
    applySuggestion(key: string, value: string): void;
    filterHints(): JSX.Element | null;
    filterHelp(filters: AuditFilter[]): JSX.Element;
    /**
     * Whether a filter offers value autocomplete (a known, enumerable value set).
     */
    filterHasAutocomplete(filter: AuditFilter): boolean;
    /**
     * Render a quick-filter chip. Filters with autocomplete open the suggestion dropdown on
     * click; the rest apply their example query (or prime the input for free-text values).
     */
    filterChip(filter: AuditFilter): JSX.Element;
    /**
     * Prime the input with a filter prefix and open its value autocomplete.
     */
    openAutocomplete(key: string): void;
    applyExample(example: string): void;
    requestParams(): any;
    refresh(clear?: boolean): Promise<void>;
    loadResults(offset?: number | undefined): Promise<ApiResponsePlural<AuditLog>>;
    loadMore(): void;
    parseResults(results: ApiResponsePlural<AuditLog>): ApiResponsePlural<AuditLog>;
}
export {};
