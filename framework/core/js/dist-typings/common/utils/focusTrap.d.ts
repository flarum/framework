import { createFocusTrap as _createFocusTrap } from 'focus-trap';
/**
 * Creates a focus trap for the given element with the given options.
 *
 * This function applies some default options that are different to the library.
 * Your own options still override these custom defaults:
 *
 * ```json
 * {
     escapeDeactivates: false,
 * }
 * ```
 *
 * @param element The element to be the focus trap, or a selector that will be used to find the element.
 *
 * @see https://github.com/focus-trap/focus-trap#readme - Library documentation
 */
declare function createFocusTrap(...args: Parameters<typeof _createFocusTrap>): ReturnType<typeof _createFocusTrap>;
export * from 'focus-trap';
export { createFocusTrap };
