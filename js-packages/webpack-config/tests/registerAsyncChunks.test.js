/**
 * @jest-environment node
 */
import compile, { registrationsIn } from './chunkCompiler.js';
import 'regenerator-runtime/runtime';
import RegisterAsyncChunksPlugin from '../src/RegisterAsyncChunksPlugin.cjs';

// The plugin memoises across compilations on a static, so each test needs a
// clean slate or the second one registers nothing.
beforeEach(() => {
  RegisterAsyncChunksPlugin.registry = {};
});

/**
 * What flarum.reg makes of a sequence of `addChunkModule` calls. Core 2.0
 * rc.8 and 2.x agree on both rules, whether they key chunks by id alone (rc.8)
 * or by namespace and id (2.x):
 *
 * - a chunk id resolves to the url path it was *first* registered with, which
 *   is where the chunk's file is loaded from;
 * - a module resolves to the chunk it was *last* registered under, which is
 *   what `asyncModuleImport` loads to reach it.
 */
function registry(registrations) {
  return {
    chunkUrlPath: (chunkId) => registrations.find((r) => r.chunkId === chunkId)?.urlPath,
    moduleChunk: (urlPath) => registrations.filter((r) => r.urlPath === urlPath).pop()?.chunkId,
  };
}

test('registers a chunk imported from the entry', async () => {
  const assets = await compile('src/forum/index.js');

  expect(registry(registrationsIn(assets['forum.js'])).chunkUrlPath('forum/components/Outer')).toBe('forum/components/Outer');
});

test('names a chunk that carries several modules after the one it was imported for', async () => {
  // Split's chunk also carries openFilter, which Split statically imports; the
  // chunk's file is Split.js, so that is what the id has to resolve to.
  const assets = await compile('src/forum/split.js');
  const resolved = registry(registrationsIn(assets['forum.js']));

  expect(resolved.chunkUrlPath('forum/components/Split')).toBe('forum/components/Split');
  expect(resolved.moduleChunk('forum/components/openFilter')).toBe('forum/components/Split');
});

// Filter statically imports Picker, so the Picker module is in two chunks: its
// own, and Filter's. When a component lazily imports both, the plugin used to
// register Picker as a passenger of Filter's chunk and then skip Picker's own
// chunk as already done — so `import('./Picker')` asked for a chunk id nothing
// had registered, and the url fell back to webpack's guess. That is flarum/deck
// opening DeckPickerModal from AddColumnModal.
describe.each([
  ['Filter first', 'src/forum/index.js', 'forum/components/Outer.js'],
  ['Picker first', 'src/forum/reversed.js', 'forum/components/OuterReversed.js'],
  // Each import in a different module of the same chunk, so they are
  // registered separately and only the plugin's memory of what that chunk has
  // already registered sits between them.
  ['imports split across modules, Filter in the helper', 'src/forum/split.js', 'forum/components/Split.js'],
  ['imports split across modules, Picker in the helper', 'src/forum/splitReversed.js', 'forum/components/SplitReversed.js'],
])('a module that also sits in another chunk (%s)', (_, fixture, importer) => {
  async function registrations() {
    const assets = await compile(fixture);

    return registrationsIn(assets[importer]);
  }

  it('registers its own chunk', async () => {
    expect(registry(await registrations()).chunkUrlPath('forum/components/Picker')).toBe('forum/components/Picker');
  });

  it('leaves the other chunk resolving to the module it is named after', async () => {
    expect(registry(await registrations()).chunkUrlPath('forum/components/Filter')).toBe('forum/components/Filter');
  });

  it('is loaded from its own chunk', async () => {
    expect(registry(await registrations()).moduleChunk('forum/components/Picker')).toBe('forum/components/Picker');
  });

  it('registers nothing twice', async () => {
    const pairs = (await registrations()).map((r) => `${r.chunkId} ${r.urlPath}`);

    expect(pairs).toEqual([...new Set(pairs)]);
  });
});
