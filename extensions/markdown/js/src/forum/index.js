/*!
 * Includes modified code from GitHub Markdown Toolbar Element
 * https://github.com/github/markdown-toolbar-element/
 *
 * Original Copyright GitHub, Inc.
 * Released under the MIT license
 * https://github.com/github/markdown-toolbar-element/blob/master/LICENSE
 */

import { extend } from 'flarum/extend';
import TextEditor from 'flarum/components/TextEditor';
import MarkdownArea from 'mdarea';

import MarkdownToolbar from './components/MarkdownToolbar';
import MarkdownButton from './components/MarkdownButton';

app.initializers.add('flarum-markdown', function(app) {
  let index = 1;

  extend(TextEditor.prototype, 'oninit', function() {
    this.textareaId = 'textarea'+(index++);
  });

  extend(TextEditor.prototype, 'view', function(vdom) {
    vdom.children[0].attrs.id = this.textareaId;
  });

  extend(TextEditor.prototype, 'oncreate', function() {
    this.editor = new MarkdownArea(this.$('textarea')[0], {
      keyMap: {
        indent: ['Ctrl+m'],
        outdent: ['Ctrl+M']
      }
    });
  });

  extend(TextEditor.prototype, 'onremove', function () {
    this.editor.destroy();
  });

  extend(TextEditor.prototype, 'toolbarItems', function(items) {
    const tooltip = name => app.translator.trans(`flarum-markdown.forum.composer.${name}_tooltip`);

    items.add('markdown', (
      <MarkdownToolbar for={this.textareaId}>
        <MarkdownButton title={tooltip('header')} icon="fas fa-heading" style={{ prefix: '### ' }} />
        <MarkdownButton title={tooltip('bold')} icon="fas fa-bold" style={{ prefix: '**', suffix: '**', trimFirst: true }} hotkey="b" />
        <MarkdownButton title={tooltip('italic')} icon="fas fa-italic" style={{ prefix: '_', suffix: '_', trimFirst: true }} hotkey="i" />
        <MarkdownButton title={tooltip('quote')} icon="fas fa-quote-left" style={{ prefix: '> ', multiline: true, surroundWithNewlines: true }} />
        <MarkdownButton title={tooltip('code')} icon="fas fa-code" style={{ prefix: '`', suffix: '`', blockPrefix: '```', blockSuffix: '```' }} />
        <MarkdownButton title={tooltip('link')} icon="fas fa-link" style={{ prefix: '[', suffix: '](https://)', replaceNext: 'https://', scanFor: 'https?://' }} />
        <MarkdownButton title={tooltip('image')} icon="fas fa-image" style={{ prefix: '![', suffix: '](https://)', replaceNext: 'https://', scanFor: 'https?://' }} />
        <MarkdownButton title={tooltip('unordered_list')} icon="fas fa-list-ul" style={{ prefix: '- ', multiline: true, surroundWithNewlines: true }} />
        <MarkdownButton title={tooltip('ordered_list')} icon="fas fa-list-ol" style={{ prefix: '1. ', multiline: true, orderedList: true }} />
      </MarkdownToolbar>
    ), 100);
  });
});
