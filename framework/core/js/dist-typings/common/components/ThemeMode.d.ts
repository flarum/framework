import Component, { type ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
export interface IThemeModeAttrs extends ComponentAttrs {
    label: string;
    mode: string;
    selected?: boolean;
    alternate?: boolean;
}
export declare enum ColorScheme {
    Auto = "auto",
    Light = "light",
    Dark = "dark",
    LightHighContrast = "light-hc",
    DarkHighContrast = "dark-hc"
}
export declare type ColorSchemeData = {
    id: ColorScheme | string;
    label?: string | null;
};
export default class ThemeMode<CustomAttrs extends IThemeModeAttrs = IThemeModeAttrs> extends Component<CustomAttrs> {
    static colorSchemes: ColorSchemeData[];
    view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;
}
