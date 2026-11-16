import app from 'flarum/forum/app';
import ComposerBody, { type IComposerBodyAttrs } from 'flarum/forum/components/ComposerBody';
import Icon from 'flarum/common/components/Icon';
import extractText from 'flarum/common/utils/extractText';
import type DialogMessage from '../../common/models/DialogMessage';

export interface IEditMessageComposerAttrs extends IComposerBodyAttrs {
  message: DialogMessage;
}

/**
 * Edits a message in the composer, starting from what it says now; the
 * message shows what is being written in its place meanwhile.
 */
export default class EditMessageComposer<
  CustomAttrs extends IEditMessageComposerAttrs = IEditMessageComposerAttrs,
> extends ComposerBody<CustomAttrs> {
  static focusOnSelector = () => '.TextEditor-editor';

  static initAttrs(attrs: IEditMessageComposerAttrs) {
    super.initAttrs(attrs);

    attrs.submitLabel = attrs.submitLabel || app.translator.trans('flarum-messages.forum.composer_edit.submit_button', {}, true);
    attrs.confirmExit = attrs.confirmExit || extractText(app.translator.trans('flarum-messages.forum.composer_edit.discard_confirmation', {}, true));
    attrs.originalContent = attrs.originalContent || attrs.message.content() || '';
    attrs.user = attrs.user || attrs.message.user();
    attrs.className = 'ComposerBody--message';
  }

  headerItems() {
    const items = super.headerItems();

    items.add(
      'title',
      <h3>
        <Icon name="fas fa-pencil-alt" /> {app.translator.trans('flarum-messages.forum.composer_edit.title')}
      </h3>
    );

    return items;
  }

  data(): Record<string, unknown> {
    return {
      content: this.composer.fields.content(),
    };
  }

  onsubmit() {
    if (this.loading) return;

    this.loading = true;

    this.attrs.message.save(this.data()).then(() => {
      this.composer.hide();
      m.redraw();
    }, this.loaded.bind(this));
  }
}
