/**
 * The `Drawer` class controls the page's drawer. The drawer is the area the
 * slides out from the left on mobile devices; it contains the header and the
 * footer.
 */
export default class Drawer {
    /**
     * Check whether or not the drawer is currently open.
     *
     * @return {Boolean}
     * @public
     */
    public isOpen(): boolean;
    /**
     * Hide the drawer.
     *
     * @public
     */
    public hide(): void;
    /**
     * Show the drawer.
     *
     * @public
     */
    public show(): void;
    $backdrop: JQuery<HTMLElement> | undefined;
}
