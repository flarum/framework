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
import BasicEditorDriver from 'flarum/common/utils/BasicEditorDriver';
import styleSelectedText from 'flarum/common/utils/styleSelectedText';

import MarkdownToolbar from './components/MarkdownToolbar';
import MarkdownButton from './components/MarkdownButton';
import ItemList from 'flarum/common/utils/ItemList';

const modifierKey = navigator.userAgent.match(/Macintosh/) ? '⌘' : 'ctrl';

const styles: Record<string, Partial<Parameters<typeof styleSelectedText>[1]>> = {
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

const applyStyle = (id: string, editorDriver: BasicEditorDriver) => {
  // This is a nasty hack that breaks encapsulation of the editor.
  // In future releases, we'll need to tweak the editor driver interface
  // to support triggering events like this.
  styleSelectedText(editorDriver.el, styles[id] as Parameters<typeof styleSelectedText>[1]);
};

function makeShortcut(id: string, key: string, editorDriver: BasicEditorDriver) {
  return function (e: KeyboardEvent) {
    if (e.key === key && ((e.metaKey && modifierKey === '⌘') || (e.ctrlKey && modifierKey === 'ctrl'))) {
      e.preventDefault();
      applyStyle(id, editorDriver);
    }
  };
}

let shortcutHandler: ((e: KeyboardEvent) => void) | undefined;

function markdownToolbarItems(this: any, oldFunc: (() => ItemList<unknown>) | undefined) {
  const items = typeof oldFunc === 'function' ? oldFunc() : new ItemList();

  function tooltip(name: string, hotkey?: string) {
    return app.translator.trans(`flarum-markdown.lib.composer.${name}_tooltip`) + (hotkey ? ` <${modifierKey}-${hotkey}>` : '');
  }

  const makeApplyStyle = (id: string) => {
    return () => applyStyle(id, this.attrs.composer.editor);
  };

  items.add('header', <MarkdownButton title={String(tooltip('header'))} icon="fas fa-heading" onclick={makeApplyStyle('header')} />, 1000);
  items.add('bold', <MarkdownButton title={String(tooltip('bold', 'b'))} icon="fas fa-bold" onclick={makeApplyStyle('bold')} />, 900);
  items.add('italic', <MarkdownButton title={String(tooltip('italic', 'i'))} icon="fas fa-italic" onclick={makeApplyStyle('italic')} />, 800);
  items.add(
    'strikethrough',
    <MarkdownButton title={String(tooltip('strikethrough'))} icon="fas fa-strikethrough" onclick={makeApplyStyle('strikethrough')} />,
    700
  );
  items.add('quote', <MarkdownButton title={String(tooltip('quote'))} icon="fas fa-quote-left" onclick={makeApplyStyle('quote')} />, 600);
  items.add(
    'spoiler',
    <MarkdownButton title={String(tooltip('spoiler'))} icon="fas fa-exclamation-triangle" onclick={makeApplyStyle('spoiler')} />,
    500
  );
  items.add('code', <MarkdownButton title={String(tooltip('code'))} icon="fas fa-code" onclick={makeApplyStyle('code')} />, 400);
  items.add('link', <MarkdownButton title={String(tooltip('link'))} icon="fas fa-link" onclick={makeApplyStyle('link')} />, 300);
  items.add('image', <MarkdownButton title={String(tooltip('image'))} icon="fas fa-image" onclick={makeApplyStyle('image')} />, 200);
  items.add(
    'unordered_list',
    <MarkdownButton title={String(tooltip('unordered_list'))} icon="fas fa-list-ul" onclick={makeApplyStyle('unordered_list')} />,
    100
  );
  items.add(
    'ordered_list',
    <MarkdownButton title={String(tooltip('ordered_list'))} icon="fas fa-list-ol" onclick={makeApplyStyle('ordered_list')} />,
    0
  );

  return items;
}

export function initialize() {
  (extend as any)(BasicEditorDriver.prototype, 'keyHandlers', function (this: BasicEditorDriver, items: ItemList<(e: KeyboardEvent) => void>) {
    items.add('bold', makeShortcut('bold', 'b', this));
    items.add('italic', makeShortcut('italic', 'i', this));
  });

  override('flarum/common/components/TextEditor', 'markdownToolbarItems', markdownToolbarItems);

  extend('flarum/common/components/TextEditor', 'toolbarItems', function (this: any, items) {
    items.add(
      'markdown',
      <MarkdownToolbar for={this.textareaId} setShortcutHandler={(handler: (e: KeyboardEvent) => void) => (shortcutHandler = handler)}>
        {this.markdownToolbarItems().toArray()}
      </MarkdownToolbar>,
      100
    );
  });
}
