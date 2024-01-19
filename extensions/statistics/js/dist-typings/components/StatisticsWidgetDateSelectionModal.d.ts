import ItemList from 'flarum/common/utils/ItemList';
import FormModal, { IFormModalAttrs } from 'flarum/common/components/FormModal';
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
export interface IStatisticsWidgetDateSelectionModalAttrs extends IFormModalAttrs {
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
export default class StatisticsWidgetDateSelectionModal extends FormModal<IStatisticsWidgetDateSelectionModalAttrs> {
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
