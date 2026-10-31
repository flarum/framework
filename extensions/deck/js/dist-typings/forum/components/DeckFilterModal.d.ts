import type Mithril from 'mithril';
import DeckPickerModal, { type IDeckPickerModalAttrs } from './DeckPickerModal';
export interface IDeckFilterModalAttrs extends IDeckPickerModalAttrs {
    /** Whether a query can be used, e.g. that it's filters only. */
    accept: (query: string) => boolean;
    invalidText?: Mithril.Children;
    onapply: (query: string) => void;
}
/**
 * Writing a column's filters in the forum's search modal, with its filter
 * suggestions and highlighting, and the discussions they match as a preview.
 * The result is the query itself, not one of the results.
 */
export default class DeckFilterModal<CustomAttrs extends IDeckFilterModalAttrs = IDeckFilterModalAttrs> extends DeckPickerModal<CustomAttrs> {
    protected error: Mithril.Children;
    /**
     * @param resource Whose filters to offer, e.g. 'discussions'.
     */
    static openFor(resource: string, attrs: {
        title: Mithril.Children;
        value: string;
        accept: (query: string) => boolean;
        invalidText?: Mithril.Children;
        onapply: (query: string) => void;
    }): void;
    className(): string;
    content(): Mithril.Children;
    gambits(): JSX.Element[];
    gambifyInput(): Mithril.Children;
    /** Enter takes a highlighted suggestion, as in search, and otherwise uses the query. */
    selectResult(): void;
    /** Results are only a preview of what the column will show. */
    protected pick(): void;
    protected apply(): void;
}
