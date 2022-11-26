import ItemList from 'flarum/common/utils/ItemList';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Mithril from 'mithril';
export interface IDateSelection {
    /**
     * Timestamp (seconds, not ms) for start date
     */
    start: number;
    /**
     * Timestamp (seconds, not ms) for end date
     */
    end: number;
}
export interface IStatisticsWidgetDateSelectionModalAttrs extends IInternalModalAttrs {
    onModalSubmit: (dates: IDateSelection) => void;
    value?: IDateSelection;
}
interface IStatisticsWidgetDateSelectionModalState {
    inputs: {
        startDateVal: string;
        endDateVal: string;
    };
    ids: {
        startDate: string;
        endDate: string;
    };
}
export default class StatisticsWidgetDateSelectionModal extends Modal<IStatisticsWidgetDateSelectionModalAttrs> {
    state: IStatisticsWidgetDateSelectionModalState;
    oninit(vnode: Mithril.Vnode<IStatisticsWidgetDateSelectionModalAttrs, this>): void;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    items(): ItemList<Mithril.Children>;
    updateState(field: keyof IStatisticsWidgetDateSelectionModalState['inputs']): (e: InputEvent) => void;
    submitData(): IDateSelection;
    onsubmit(e: SubmitEvent): void;
}
export {};
