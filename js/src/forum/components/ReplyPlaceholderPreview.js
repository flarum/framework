/*global s9e*/

export default class ReplyPlaceholderPreview {
  view() {
    return <div className="Post-body" />;
  }

  oncreate(vnode) {
    // Every 50ms, if the composer content has changed, then update the post's
    // body with a preview.
    let preview;
    this.updateInterval = setInterval(() => {
      // Since we're polling, the composer may have been closed in the meantime,
      // so we bail in that case.
      if (!app.composer.isVisible()) return;

      const content = app.composer.fields.content();

      if (preview === content) return;

      preview = content;

      const anchorToBottom = $(window).scrollTop() + $(window).height() >= $(document).height();

      s9e.TextFormatter.preview(preview || '', vnode.dom);

      if (anchorToBottom) {
        $(window).scrollTop($(document).height());
      }
    }, 50);
  }

  onremove(vnode) {
    clearInterval(this.updateInterval);
  }
}
