import Component, { type ComponentAttrs } from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import ComposerState from '../states/ComposerState';
import type Mithril from 'mithril';
export interface IComposerBodyAttrs extends ComponentAttrs {
    composer: ComposerState;
    originalContent?: string;
    submitLabel: string;
    placeholder: string;
    user: any;
    confirmExit: string;
    disabled: boolean;
    onTextEditorBuilt?: Function | null;
}
/**
 * The `ComposerBody` component handles the body, or the content, of the
 * composer. Subclasses should implement the `onsubmit` method and override
 * `headerTimes`.
 */
export default abstract class ComposerBody<CustomAttrs extends IComposerBodyAttrs = IComposerBodyAttrs> extends Component<CustomAttrs> {
    protected loading: boolean;
    protected composer: ComposerState;
    protected jumpToPreview?: () => void;
    static focusOnSelector: null | (() => string);
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    view(): JSX.Element;
    /**
     * Check if there is any unsaved data.
     */
    hasChanges(): boolean;
    /**
     * Build an item list for the composer's header.
     */
    headerItems(): ItemList<Mithril.Children>;
    /**
     * Handle the submit event of the text editor.
     */
    abstract onsubmit(): void;
    /**
     * Stop loading.
     */
    loaded(): void;
}
