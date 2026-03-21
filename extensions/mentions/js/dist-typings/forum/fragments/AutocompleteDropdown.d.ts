export default class AutocompleteDropdown extends Fragment {
    items: any[];
    active: boolean;
    index: number;
    keyWasJustPressed: boolean;
    view(): JSX.Element;
    show(left: any, top: any): void;
    hide(): void;
    navigate(delta: any): void;
    keyWasJustPressedTimeout: NodeJS.Timeout | undefined;
    complete(): void;
    setIndex(index: any, scrollToItem: any): void;
}
import Fragment from "flarum/common/Fragment";
