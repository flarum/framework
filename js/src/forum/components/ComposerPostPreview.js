/*global s9e*/

import Component from '../../common/Component';

/**
 * The `ComposerPostPreview` component renders Markdown as HTML using the
 * TextFormatter library, polling a data source for changes every 50ms. This is
 * done to prevent expensive redraws on e.g. every single keystroke, while
 * still retaining the perception of live updates for the user.
 *
 * ### Attrs
 *
 * - `composer` The state of the composer controlling this preview.
 * - `className` A CSS class for the element surrounding the preview.
 * - `surround` A callback that can execute code before and after re-render, e.g. for scroll anchoring.
 */
export default class ComposerPostPreview extends Component {
  static initAttrs(attrs) {
    attrs.className = attrs.className || '';
    attrs.surround = attrs.surround || ((preview) => preview());
  }

  view() {
    return <div className={this.attrs.className} />;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    // Every 50ms, if the composer content has changed, then update the post's
    // body with a preview.
    let preview;
    const updatePreview = () => {
      // Since we're polling, the composer may have been closed in the meantime,
      // so we bail in that case.
      if (!this.attrs.composer.isVisible()) return;

      const content = this.attrs.composer.fields.content();

      if (preview === content) return;

      preview = content;

      this.attrs.surround(() => s9e.TextFormatter.preview(preview || '', vnode.dom));
    };
    updatePreview();

    this.updateInterval = setInterval(updatePreview, 50);
  }

  onremove() {
    clearInterval(this.updateInterval);
  }
}
