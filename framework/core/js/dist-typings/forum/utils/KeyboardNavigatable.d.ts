type KeyboardEventHandler = (event: KeyboardEvent) => void;
type ShouldHandle = (event: KeyboardEvent) => boolean;
/**
 * The `KeyboardNavigatable` class manages lists that can be navigated with the
 * keyboard, calling callbacks for each actions.
 *
 * This helper encapsulates the key binding logic, providing a simple fluent
 * API for use.
 */
export default class KeyboardNavigatable {
    /**
     * Callback to be executed for a specified input.
     */
    protected callbacks: Map<number, KeyboardEventHandler>;
    /**
     * Callback that determines whether keyboard input should be handled.
     * By default, always handle keyboard navigation.
     */
    protected whenCallback: ShouldHandle;
    /**
     * Provide a callback to be executed when navigating upwards.
     *
     * This will be triggered by the Up key.
     */
    onUp(callback: KeyboardEventHandler): KeyboardNavigatable;
    /**
     * Provide a callback to be executed when navigating downwards.
     *
     * This will be triggered by the Down key.
     */
    onDown(callback: KeyboardEventHandler): KeyboardNavigatable;
    /**
     * Provide a callback to be executed when the current item is selected..
     *
     * This will be triggered by the Return key (and Tab key, if not disabled).
     */
    onSelect(callback: KeyboardEventHandler, ignoreTabPress?: boolean): KeyboardNavigatable;
    /**
     * Provide a callback to be executed when the current item is tabbed into.
     *
     * This will be triggered by the Tab key.
     */
    onTab(callback: KeyboardEventHandler): KeyboardNavigatable;
    /**
     * Provide a callback to be executed when the navigation is canceled.
     *
     * This will be triggered by the Escape key.
     */
    onCancel(callback: KeyboardEventHandler): KeyboardNavigatable;
    /**
     * Provide a callback to be executed when previous input is removed.
     *
     * This will be triggered by the Backspace key.
     */
    onRemove(callback: KeyboardEventHandler): KeyboardNavigatable;
    /**
     * Provide a callback that determines whether keyboard input should be handled.
     */
    when(callback: ShouldHandle): KeyboardNavigatable;
    /**
     * Set up the navigation key bindings on the given jQuery element.
     */
    bindTo($element: JQuery<HTMLElement>): void;
    /**
     * Interpret the given keyboard event as navigation commands.
     */
    navigate(event: KeyboardEvent): void;
}
export {};
