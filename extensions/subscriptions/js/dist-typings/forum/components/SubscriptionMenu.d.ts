/// <reference types="mithril" />
import Dropdown, { IDropdownAttrs } from 'flarum/common/components/Dropdown';
import type Discussion from 'flarum/common/models/Discussion';
export interface ISubscriptionMenuAttrs extends IDropdownAttrs {
    discussion: Discussion;
}
export default class SubscriptionMenu<CustomAttrs extends ISubscriptionMenuAttrs = ISubscriptionMenuAttrs> extends Dropdown<CustomAttrs> {
    private options;
    private possibleButtonAttrs;
    view(): JSX.Element;
    saveSubscription(discussion: Discussion, subscription: string | null): void;
}
