/**
 * The `Drawer` class controls the page's drawer. The drawer is the area the
 * slides out from the left on mobile devices; it contains the header and the
 * footer.
 */
export default class Drawer {
    /**
     * @type {import('./focusTrap').FocusTrap}
     */
    focusTrap: import('./focusTrap').FocusTrap;
    /**
     * @type {HTMLDivElement}
     */
    appElement: HTMLDivElement;
    /**
     * @internal
     * @type {MediaQueryList}
     */
    drawerAvailableMediaQuery: MediaQueryList;
    /**
     * Handler for the `resize` event on `window`.
     *
     * This is used to close the drawer when the viewport is widened past the `phone` size.
     * At this point, the drawer turns into the standard header that we see on desktop, but
     * the drawer is still registered as 'open' internally.
     *
     * This causes issues with the focus trap, resulting in focus becoming trapped within
     * the header on desktop viewports.
     *
     * @internal
     */
    resizeHandler: (e: any) => void;
    /**
     * Check whether or not the drawer is currently open.
     *
     * @return {boolean}
     */
    isOpen(): boolean;
    /**
     * Hide the drawer.
     */
    hide(): void;
    /**
     * Show the drawer.
     */
    show(): void;
    $backdrop: JQuery<HTMLElement> | undefined;
}
