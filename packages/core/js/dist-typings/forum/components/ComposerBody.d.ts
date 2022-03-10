/**
 * The `ComposerBody` component handles the body, or the content, of the
 * composer. Subclasses should implement the `onsubmit` method and override
 * `headerTimes`.
 *
 * ### Attrs
 *
 * - `composer`
 * - `originalContent`
 * - `submitLabel`
 * - `placeholder`
 * - `user`
 * - `confirmExit`
 * - `disabled`
 *
 * @abstract
 */
export default class ComposerBody extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    composer: any;
    /**
     * Whether or not the component is loading.
     *
     * @type {Boolean}
     */
    loading: boolean | undefined;
    /**
     * Check if there is any unsaved data.
     *
     * @return {boolean}
     */
    hasChanges(): boolean;
    /**
     * Build an item list for the composer's header.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    headerItems(): ItemList<import('mithril').Children>;
    /**
     * Handle the submit event of the text editor.
     *
     * @abstract
     */
    onsubmit(): void;
    /**
     * Stop loading.
     */
    loaded(): void;
}
import Component from "../../common/Component";
import ItemList from "../../common/utils/ItemList";
