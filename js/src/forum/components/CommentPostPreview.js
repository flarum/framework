/*global s9e*/

import Component from "../../common/Component";

export default class CommentPostPreview extends Component {
  view() {
    return <div className="Post-preview" />;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    // Every 50ms, if the composer content has changed, then update the post's
    // body with a preview.
    let preview;
    const updatePreview = () => {
      const content = app.composer.fields.content();

      if (preview === content) return;

      preview = content;

      s9e.TextFormatter.preview(preview || '', vnode.dom);
    };
    updatePreview();

    this.updateInterval = setInterval(updatePreview, 50);
  }

  onremove(vnode) {
    clearInterval(this.updateInterval);
  }
}
