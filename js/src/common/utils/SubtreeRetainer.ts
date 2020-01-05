export default class SubtreeRetainer {
    callbacks: Function[];
    data = {};

    constructor(...callbacks: Function[]) {
        this.callbacks = callbacks;
    }

    check(...callbacks: Function[]) {
        this.callbacks.concat(...callbacks);
    }

    /**
     * Return whether the component should redraw.
     */
    update(): boolean {
        let update = false;

        this.callbacks.forEach((callback, i) => {
            const result = callback();

            if (result !== this.data[i]) {
                this.data[i] = result;
                update = true;
            }
        });

        return update;
    }
}
