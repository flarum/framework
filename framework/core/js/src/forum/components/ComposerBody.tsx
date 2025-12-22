import Component, { type ComponentAttrs } from '../../common/Component';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import ConfirmDocumentUnload from '../../common/components/ConfirmDocumentUnload';
import TextEditor from '../../common/components/TextEditor';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';
import classList from '../../common/utils/classList';
import Avatar from '../../common/components/Avatar';
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
  protected loading = false;
  protected composer!: ComposerState;
  protected jumpToPreview?: () => void;

  static focusOnSelector: null | (() => string) = null;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.composer = this.attrs.composer;

    // Let the composer state know to ask for confirmation under certain
    // circumstances, if the body supports / requires it and has a corresponding
    // confirmation question to ask.
    if (this.attrs.confirmExit) {
      this.composer.preventClosingWhen(() => this.hasChanges(), this.attrs.confirmExit);
    }

    this.composer.fields!.content(this.attrs.originalContent || '');
  }

  view() {
    return (
      <ConfirmDocumentUnload when={this.hasChanges.bind(this)}>
        <div className={classList('ComposerBody', this.attrs.className)}>
          <Avatar user={this.attrs.user} className="ComposerBody-avatar" />
          <div className="ComposerBody-content">
            <ul className="ComposerBody-header">{listItems(this.headerItems().toArray())}</ul>
            <div className="ComposerBody-editor">
              <TextEditor
                submitLabel={this.attrs.submitLabel}
                placeholder={this.attrs.placeholder}
                disabled={this.loading || this.attrs.disabled}
                composer={this.composer}
                preview={this.jumpToPreview?.bind(this)}
                onchange={this.composer.fields!.content}
                onsubmit={this.onsubmit.bind(this)}
                value={this.composer.fields!.content()}
                onTextEditorBuilt={this.attrs.onTextEditorBuilt}
              />
            </div>
          </div>
          <LoadingIndicator display="unset" containerClassName={classList('ComposerBody-loading', this.loading && 'active')} size="large" />
        </div>
      </ConfirmDocumentUnload>
    );
  }

  /**
   * Check if there is any unsaved data.
   */
  hasChanges(): boolean {
    const content = this.composer.fields!.content();

    return Boolean(content) && content !== this.attrs.originalContent;
  }

  /**
   * Build an item list for the composer's header.
   */
  headerItems() {
    return new ItemList<Mithril.Children>();
  }

  /**
   * Handle the submit event of the text editor.
   */
  abstract onsubmit(): void;

  /**
   * Stop loading.
   */
  loaded() {
    this.loading = false;
    m.redraw();
  }
}
