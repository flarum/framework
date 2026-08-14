import Component, { type ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
export default class ThemeSwitcher<CustomAttrs extends ComponentAttrs = ComponentAttrs> extends Component<CustomAttrs> {
    iconItems(): ItemList<string>;
    icon(scheme: string): string;
    active(): string;
    label(scheme: string): string;
    select(scheme: string): void;
    view(): Mithril.Children;
}
