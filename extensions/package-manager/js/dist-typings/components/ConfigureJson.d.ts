import type Mithril from 'mithril';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import { CommonSettingsItemOptions, type SettingsComponentOptions } from '@flarum/core/src/admin/components/AdminPage';
import type ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
export interface IConfigureJson extends ComponentAttrs {
    buildSettingComponent: (entry: ((this: this) => Mithril.Children) | SettingsComponentOptions) => Mithril.Children;
}
export default abstract class ConfigureJson<CustomAttrs extends IConfigureJson = IConfigureJson> extends Component<CustomAttrs> {
    protected settings: Record<string, Stream<any>>;
    protected initialSettings: Record<string, any> | null;
    protected loading: boolean;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    protected abstract type: string;
    abstract title(): Mithril.Children;
    abstract content(): Mithril.Children;
    className(): string;
    view(): Mithril.Children;
    submitButton(): Mithril.Children[];
    customSettingComponents(): ItemList<(attributes: CommonSettingsItemOptions) => Mithril.Children>;
    setting(key: string): Stream<any>;
    submit(readOnly: boolean): void;
    isDirty(): boolean;
}
