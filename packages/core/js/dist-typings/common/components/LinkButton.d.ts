/**
 * The `LinkButton` component defines a `Button` which links to a route.
 *
 * ### Attrs
 *
 * All of the attrs accepted by `Button`, plus:
 *
 * - `active` Whether or not the page that this button links to is currently
 *   active.
 * - `href` The URL to link to. If the current URL `m.route()` matches this,
 *   the `active` prop will automatically be set to true.
 * - `force` Whether the page should be fully rerendered. Defaults to `true`.
 */
export default class LinkButton extends Button<import("./Button").IButtonAttrs> {
    static initAttrs(attrs: any): void;
    /**
     * Determine whether a component with the given attrs is 'active'.
     *
     * @param {object} attrs
     * @return {boolean}
     */
    static isActive(attrs: object): boolean;
    constructor();
}
import Button from "./Button";
