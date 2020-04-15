/**
 * The `Drawer` class controls the page's drawer. The drawer is the area the
 * slides out from the left on mobile devices; it contains the header and the
 * footer.
 */
export default class Drawer {
    private $backdrop?: ZeptoCollection;

    constructor() {
        // Set up an event handler so that whenever the content area is tapped,
        // the drawer will close.
        $('#content').click((e) => {
            if (this.isOpen()) {
                e.preventDefault();
                this.hide();
            }
        });
    }

    /**
     * Check whether or not the drawer is currently open.
     */
    isOpen(): boolean {
        return $('#app').hasClass('drawerOpen');
    }

    /**
     * Hide the drawer.
     */
    hide() {
        $('#app').removeClass('drawerOpen');

        if (this.$backdrop) this.$backdrop.remove();
    }

    /**
     * Show the drawer.
     */
    public show() {
        $('#app').addClass('drawerOpen');

        this.$backdrop = $('<div/>')
            .addClass('drawer-backdrop fade')
            .appendTo('body')
            .click(() => this.hide());

        requestAnimationFrame(() => this.$backdrop!.addClass('in'));
    }
}
