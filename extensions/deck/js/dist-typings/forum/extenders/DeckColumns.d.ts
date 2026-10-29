import type ForumApplication from 'flarum/forum/ForumApplication';
import type IExtender from 'flarum/common/extenders/IExtender';
import type { IExtensionModule } from 'flarum/common/extenders/IExtender';
import type { DeckColumnType } from '../columns/DeckColumnType';
/**
 * Registers column types for Deck. From an extension that supports Deck
 * optionally, list `flarum/deck` under `optional-dependencies` (so Deck loads
 * first) and only add the extender while it's enabled:
 *
 *     import DeckColumns from 'ext:flarum/deck/forum/extenders/DeckColumns';
 *
 *     // in your extend.ts array:
 *     ...('flarum-deck' in flarum.extensions
 *       ? [new DeckColumns().add('acme-bookmarks', { ... }, 20)]
 *       : []),
 *
 * (flarum-webpack-config finds a module's default export with a regex over its
 * source, comments included, so the usual array export can't be spelled out
 * here without unregistering this module.)
 *
 * Prefix keys with your extension's name; they're stored in members' layouts.
 * Priority orders the add-column modal (Deck's own run from 100 down to 20).
 */
export default class DeckColumns implements IExtender<ForumApplication> {
    private readonly types;
    add(key: string, type: DeckColumnType, priority?: number): this;
    extend(app: ForumApplication, extension: IExtensionModule): void;
}
