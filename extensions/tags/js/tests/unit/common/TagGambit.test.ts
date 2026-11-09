import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { jest } from '@jest/globals';

import TagGambit from '../../../src/common/query/discussions/TagGambit';
import Tag from '../../../src/common/models/Tag';

const mockTag = (slug: string, name: string) =>
  ({
    slug: () => slug,
    name: () => name,
  }) as unknown as Tag;

beforeAll(() => {
  bootstrapForum();
  app.boot();
  (app.store.models as any).tags = Tag;
});

describe('TagGambit', () => {
  beforeEach(() => {
    TagGambit.loadingTags = false;
    TagGambit.loadedTags = false;
    TagGambit.lastLoadFailedAt = 0;
    (global as any).m = { redraw: jest.fn() };
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  it('lazily loads complete visible tag list and redraws when store is initially incomplete', async () => {
    let tagsInStore = [mockTag('general', 'General')];
    app.store.all = jest.fn().mockImplementation((type: string) => {
      if (type === 'tags') return tagsInStore;
      return [];
    });

    let resolveLoad: (tags: Tag[]) => void;
    const loadPromise = new Promise<Tag[]>((resolve) => {
      resolveLoad = resolve;
    });
    const mockLoad = jest.fn().mockReturnValue(loadPromise);

    (app as any).tagList = {
      load: mockLoad,
    };

    const gambit = new TagGambit();

    // 1. First call with initially incomplete store (e.g. fresh visit before visiting Tags page)
    const initialValues = gambit.values();
    expect(initialValues).toEqual([{ value: 'general', label: 'General' }]);
    expect(mockLoad).toHaveBeenCalledTimes(1);
    expect(mockLoad).toHaveBeenCalledWith(['parent', 'children']);

    // 2. Subsequent call while in-flight (e.g. next keystrokes) does not trigger another load
    const inFlightGambit = new TagGambit();
    const inFlightValues = inFlightGambit.values();
    expect(inFlightValues).toEqual([{ value: 'general', label: 'General' }]);
    expect(mockLoad).toHaveBeenCalledTimes(1);

    // 3. Resolve the load with complete tag list (including child-only tag)
    const childTag = mockTag('child-only', 'Child Only');
    tagsInStore = [mockTag('general', 'General'), childTag];
    resolveLoad!([mockTag('general', 'General'), childTag]);
    await loadPromise;

    expect((global as any).m.redraw).toHaveBeenCalled();

    // 4. After load completes, new suggestions include child-only
    const afterLoadGambit = new TagGambit();
    const loadedValues = afterLoadGambit.values();
    expect(loadedValues).toEqual([
      { value: 'general', label: 'General' },
      { value: 'child-only', label: 'Child Only' },
    ]);

    // 5. Subsequent calls when already loaded do not trigger load again
    const finalGambit = new TagGambit();
    finalGambit.values();
    expect(mockLoad).toHaveBeenCalledTimes(1);
  });

  it('consumes rejected load promise and prevents redraw-driven retries', async () => {
    let tagsInStore = [mockTag('general', 'General')];
    app.store.all = jest.fn().mockImplementation((type: string) => {
      if (type === 'tags') return tagsInStore;
      return [];
    });

    const mockLoad = jest.fn().mockRejectedValue(new Error('503 Service Unavailable'));

    (app as any).tagList = {
      load: mockLoad,
    };

    const gambit = new TagGambit();

    // Initial call triggers load
    const initialValues = gambit.values();
    expect(initialValues).toEqual([{ value: 'general', label: 'General' }]);
    expect(mockLoad).toHaveBeenCalledTimes(1);

    // Wait for promise rejection to be consumed
    await new Promise((resolve) => setTimeout(resolve, 10));

    // Subsequent renders (e.g. redraws from error handling) should NOT retry immediately
    for (let i = 0; i < 5; i++) {
      const redrawGambit = new TagGambit();
      const redrawValues = redrawGambit.values();
      expect(redrawValues).toEqual([{ value: 'general', label: 'General' }]);
    }

    expect(mockLoad).toHaveBeenCalledTimes(1);
  });
});
