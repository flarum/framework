/*!
 * Includes modified code from GitHub Markdown Toolbar Element
 * https://github.com/github/markdown-toolbar-element/
 *
 * Original Copyright GitHub, Inc.
 * Released under the MIT license
 * https://github.com/github/markdown-toolbar-element/blob/master/LICENSE
 */

import app from 'flarum/common/app';
import { extend, override } from 'flarum/common/extend';
import TextEditor from 'flarum/common/components/TextEditor';
import BasicEditorDriver from 'flarum/common/utils/BasicEditorDriver';
import styleSelectedText from 'flarum/common/utils/styleSelectedText';

import MarkdownToolbar from './components/MarkdownToolbar';
import MarkdownButton from './components/MarkdownButton';
import ItemList from 'flarum/common/utils/ItemList';

const modifierKey = navigator.userAgent.match(/Macintosh/) ? '⌘' : 'ctrl';

const styles = {
  header: { prefix: '### ' },
  bold: { prefix: '**', suffix: '**', trimFirst: true },
  italic: { prefix: '_', suffix: '_', trimFirst: true },
  strikethrough: { prefix: '~~', suffix: '~~', trimFirst: true },
  quote: { prefix: '> ', multiline: true, surroundWithNewlines: true },
  code: { prefix: '`', suffix: '`', blockPrefix: '```', blockSuffix: '```' },
  link: { prefix: '[', suffix: '](https://)', replaceNext: 'https://', scanFor: 'https?://' },
  image: { prefix: '![', suffix: '](https://)', replaceNext: 'https://', scanFor: 'https?://' },
  unordered_list: { prefix: '- ', multiline: true, surroundWithNewlines: true },
  ordered_list: { prefix: '1. ', multiline: true, orderedList: true },
  spoiler: { prefix: '>!', suffix: '!<', blockPrefix: '>! ', multiline: true, trimFirst: true },
};

const applyStyle = (id, editorDriver) => {
  // This is a nasty hack that breaks encapsulation of the editor.
  // In future releases, we'll need to tweak the editor driver interface
  // to support triggering events like this.
  styleSelectedText(editorDriver.el, styles[id]);
};

function makeShortcut(id, key, editorDriver) {
  return function (e) {
    if (e.key === key && ((e.metaKey && modifierKey === '⌘') || (e.ctrlKey && modifierKey === 'ctrl'))) {
      e.preventDefault();
      applyStyle(id, editorDriver);
    }
  };
}

function markdownToolbarItems(oldFunc) {
  const items = typeof oldFunc === 'function' ? oldFunc() : new ItemList();

  function tooltip(name, hotkey) {
    return app.translator.trans(`flarum-markdown.lib.composer.${name}_tooltip`) + (hotkey ? ` <${modifierKey}-${hotkey}>` : '');
  }

  const makeApplyStyle = (id) => {
    return () => applyStyle(id, this.attrs.composer.editor);
  };

  items.add('header', <MarkdownButton title={tooltip('header')} icon="fas fa-heading" onclick={makeApplyStyle('header')} />, 1000);
  items.add('bold', <MarkdownButton title={tooltip('bold', 'b')} icon="fas fa-bold" onclick={makeApplyStyle('bold')} />, 900);
  items.add('italic', <MarkdownButton title={tooltip('italic', 'i')} icon="fas fa-italic" onclick={makeApplyStyle('italic')} />, 800);
  items.add(
    'strikethrough',
    <MarkdownButton title={tooltip('strikethrough')} icon="fas fa-strikethrough" onclick={makeApplyStyle('strikethrough')} />,
    700
  );
  items.add('quote', <MarkdownButton title={tooltip('quote')} icon="fas fa-quote-left" onclick={makeApplyStyle('quote')} />, 600);
  items.add('spoiler', <MarkdownButton title={tooltip('spoiler')} icon="fas fa-exclamation-triangle" onclick={makeApplyStyle('spoiler')} />, 500);
  items.add('code', <MarkdownButton title={tooltip('code')} icon="fas fa-code" onclick={makeApplyStyle('code')} />, 400);
  items.add('link', <MarkdownButton title={tooltip('link')} icon="fas fa-link" onclick={makeApplyStyle('link')} />, 300);
  items.add('image', <MarkdownButton title={tooltip('image')} icon="fas fa-image" onclick={makeApplyStyle('image')} />, 200);
  items.add(
    'unordered_list',
    <MarkdownButton title={tooltip('unordered_list')} icon="fas fa-list-ul" onclick={makeApplyStyle('unordered_list')} />,
    100
  );
  items.add('ordered_list', <MarkdownButton title={tooltip('ordered_list')} icon="fas fa-list-ol" onclick={makeApplyStyle('ordered_list')} />, 0);

  return items;
}

export function initialize(app) {
  extend(BasicEditorDriver.prototype, 'keyHandlers', function (items) {
    items.add('bold', makeShortcut('bold', 'b', this));
    items.add('italic', makeShortcut('italic', 'i', this));
  });

  if (TextEditor.prototype.markdownToolbarItems) {
    override(TextEditor.prototype, 'markdownToolbarItems', markdownToolbarItems);
  } else {
    TextEditor.prototype.markdownToolbarItems = markdownToolbarItems;
  }

  extend(TextEditor.prototype, 'toolbarItems', function (items) {
    items.add(
      'markdown',
      <MarkdownToolbar for={this.textareaId} setShortcutHandler={(handler) => (shortcutHandler = handler)}>
        {this.markdownToolbarItems().toArray()}
      </MarkdownToolbar>,
      100
    );
  });
}
