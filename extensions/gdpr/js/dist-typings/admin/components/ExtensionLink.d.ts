/// <reference types="mithril" />
import { Extension } from 'flarum/admin/AdminApplication';
import Component from 'flarum/common/Component';
export interface ExtensionLinkAttrs {
    extension: Extension | null;
}
export default class ExtensionLink extends Component<ExtensionLinkAttrs> {
    view(): JSX.Element | null;
}
