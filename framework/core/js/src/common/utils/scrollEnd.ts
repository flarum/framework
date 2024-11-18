/**
 * A utility function that waits for the scroll to end.
 * Due to lack of support from some browsers, this is a workaround for `scrollend` event.
 * 
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Element/scrollTo#behavior
 * @see https://caniuse.com/mdn-api_element_scrollend_event
 * @param container The container element to wait scroll end on.
 * @returns A promise that resolves when the scroll is ended.
 */
export default function scrollEnd(container: HTMLElement): Promise<void> {
    let lastTop = container.scrollTop;

    return new Promise((resolve) => {
        const animFrame = () => {
            if (lastTop === container.scrollTop) {
                resolve();
            } else {
                requestAnimationFrame(animFrame);
                lastTop = container.scrollTop;
            }
        };
        requestAnimationFrame(animFrame);
    });
}