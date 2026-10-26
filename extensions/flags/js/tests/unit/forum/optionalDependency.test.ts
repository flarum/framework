import { jest } from '@jest/globals';

/**
 * The Flagged posts column builds on an optional dependency. Without it,
 * everything imported from it resolves to undefined, and Flags still has to
 * load: a class extending one of its exports at the top level of a module took
 * the whole bundle down with it.
 */
jest.unstable_mockModule('ext:flarum/deck/forum/columns/PostListSource', () => ({ default: undefined }));
jest.unstable_mockModule('ext:flarum/deck/forum/extenders/DeckColumns', () => ({ default: undefined }));
jest.unstable_mockModule('ext:flarum/deck/forum/states/deckListStates', () => ({ asQuery: undefined }));

it('loads the Flagged posts source without its optional dependency', async () => {
  await expect(import('../../../src/forum/FlaggedPostsSource')).resolves.toBeDefined();
});

// What extend.ts imports to add the column, only calling it alongside the dependency.
it('loads the column integration without its optional dependency', async () => {
  await expect(import('../../../src/forum/extendDeck')).resolves.toBeDefined();
});
