import Fragment from 'flarum/common/Fragment';
import type Mithril from 'mithril';
export default class AutocompleteDropdown extends Fragment {
    items: Mithril.Vnode[];
    active: boolean;
    index: number;
    keyWasJustPressed: boolean;
    keyWasJustPressedTimeout: ReturnType<typeof setTimeout> | null;
    view(): Mithril.Vnode<Mithril.Attributes, this>;
    show(left?: number, top?: number): void;
    hide(): void;
    navigate(delta: number): void;
    complete(): void;
    setIndex(index: number, scrollToItem?: boolean): void;
}
