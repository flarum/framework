import Component, { type ComponentAttrs } from 'flarum/common/Component';
interface IRecipientGroupSettingsAttrs extends ComponentAttrs {
    value: string;
    onchange: (value: string) => void;
}
export default class RecipientGroupSettings extends Component<IRecipientGroupSettingsAttrs> {
    view(): JSX.Element;
}
export {};
