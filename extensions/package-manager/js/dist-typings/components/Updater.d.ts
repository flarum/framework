/// <reference types="mithril" />
import Component, { ComponentAttrs } from 'flarum/common/Component';
import ItemList from 'flarum/common/utils/ItemList';
export interface IUpdaterAttrs extends ComponentAttrs {
}
export declare type UpdaterLoadingTypes = 'check' | 'minor-update' | 'global-update' | 'extension-update' | null;
export default class Updater extends Component<IUpdaterAttrs> {
    view(): (JSX.Element | null)[];
    lastUpdateCheckView(): JSX.Element | null;
    availableUpdatesView(): JSX.Element;
    controlItems(): ItemList<unknown>;
}
