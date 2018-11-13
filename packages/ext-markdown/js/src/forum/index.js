import { extend } from 'flarum/extend';
import TextEditor from 'flarum/components/TextEditor';
import icon from 'flarum/helpers/icon';

import '@webcomponents/custom-elements';
import '@github/markdown-toolbar-element';
import MarkdownArea from 'mdarea/mdarea.js';

app.initializers.add('flarum-markdown', function(app) {
  let index = 1;

  extend(TextEditor.prototype, 'init', function() {
    this.textareaId = 'textarea'+(index++);
  });

  extend(TextEditor.prototype, 'view', function(vdom) {
    vdom.children[0].attrs.id = this.textareaId;
  });

  extend(TextEditor.prototype, 'configTextarea', function(value, element, isInitialized, context) {
    if (isInitialized) return;

    const editor = new MarkdownArea(element);
    editor.disableInline();

    context.onunload = function() {
      editor.destroy();
    };
  });

  extend(TextEditor.prototype, 'toolbarItems', function(items) {
    const attrs = {
      className: 'Button Button--icon Button--link',
      config: elm => $(elm).tooltip()
    };

    const tooltip = name => app.translator.trans(`flarum-markdown.forum.composer.${name}_tooltip`);

    items.add('markdown', (
      <markdown-toolbar for={this.textareaId}>
        <md-header title={tooltip('header')} {...attrs}>{icon('fas fa-heading')}</md-header>
        <md-bold title={tooltip('bold')+' <cmd-b>'} {...attrs}>{icon('fas fa-bold')}</md-bold>
        <md-italic title={tooltip('italic')+' <cmd-i>'} {...attrs}>{icon('fas fa-italic')}</md-italic>
        <md-quote title={tooltip('quote')} {...attrs}>{icon('fas fa-quote-left')}</md-quote>
        <md-code title={tooltip('code')} {...attrs}>{icon('fas fa-code')}</md-code>
        <md-link title={tooltip('link')+' <cmd-k>'} {...attrs}>{icon('fas fa-link')}</md-link>
        <md-unordered-list title={tooltip('unordered_list')} {...attrs}>{icon('fas fa-list-ul')}</md-unordered-list>
        <md-ordered-list title={tooltip('ordered_list')} {...attrs}>{icon('fas fa-list-ol')}</md-ordered-list>
      </markdown-toolbar>
    ), 100);
  });
});
