/*global s9e*/

import Component from '../../common/Component';

/**
 * The `TextFormatterPreview` component renders Markdown as HTML using the
 * TextFormatter library, polling a data source for changes every 50ms. This is
 * done to prevent expensive redraws on e.g. every single keystroke, while
 * still retaining the perception of live updates for the user.
 *
 * ### Attrs
 *
 * - `content` A callback that retrieves the latest content that should be rendered.
 */
export default class TextFormatterPreview extends Component {
  view() {
    return <div className="Post-preview" />;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    // Every 50ms, if the composer content has changed, then update the post's
    // body with a preview.
    let preview;
    const updatePreview = () => {
      const content = this.attrs.content();

      if (preview === content) return;

      preview = content;

      s9e.TextFormatter.preview(preview || '', vnode.dom);
    };
    updatePreview();

    this.updateInterval = setInterval(updatePreview, 50);
  }

  onremove() {
    clearInterval(this.updateInterval);
  }
}
