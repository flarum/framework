/// <reference types="mithril" />
import Component, { ComponentAttrs } from 'flarum/common/Component';
export interface IUpdaterAttrs extends ComponentAttrs {
}
export type UpdaterLoadingTypes = 'check' | 'minor-update' | 'global-update' | 'extension-update' | null;
export default class Updater extends Component<IUpdaterAttrs> {
    view(): (JSX.Element | null)[];
    lastUpdateCheckView(): any;
    availableUpdatesView(): JSX.Element;
    controlItems(): any;
}
