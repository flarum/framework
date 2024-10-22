import type Mithril from 'mithril';
import Component, { ComponentAttrs } from '../Component';
export interface IColorPreviewInputAttrs extends ComponentAttrs {
    value: string;
    id?: string;
    type?: string;
    onchange?: (event: {
        target: {
            value: string;
        };
    }) => void;
}
export default class ColorPreviewInput<CustomAttributes extends IColorPreviewInputAttrs = IColorPreviewInputAttrs> extends Component<CustomAttributes> {
    view(vnode: Mithril.Vnode<CustomAttributes, this>): JSX.Element;
}
