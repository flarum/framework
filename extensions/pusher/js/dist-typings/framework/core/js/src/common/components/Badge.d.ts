/// <reference types="mithril" />
import Component, { ComponentAttrs } from '../Component';
export interface IBadgeAttrs extends ComponentAttrs {
    icon: string;
    type?: string;
    label?: string;
    color?: string;
}
/**
 * The `Badge` component represents a user/discussion badge, indicating some
 * status (e.g. a discussion is stickied, a user is an admin).
 *
 * A badge may have the following special attrs:
 *
 * - `type` The type of badge this is. This will be used to give the badge a
 *   class name of `Badge--{type}`.
 * - `icon` The name of an icon to show inside the badge.
 * - `label`
 *
 * All other attrs will be assigned as attributes on the badge element.
 */
export default class Badge<CustomAttrs extends IBadgeAttrs = IBadgeAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
}
