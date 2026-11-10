import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../src/forum/app';
import SearchManager from '../../../src/common/SearchManager';

/**
 * The minimum query length is a frontend gate deciding when a search actually
 * fires. Experimental CJK search mode lowers it to 1, because a single
 * character is often a whole word in languages without word spaces and the
 * substring match backing that mode has no minimum token length. Every other
 * forum keeps the default so a stray keystroke does not fire a search.
 */
beforeAll(() => {
  bootstrapForum();
  app.boot();
});

function setCjkMode(enabled: boolean | undefined): void {
  (app.data.settings as Record<string, unknown>) = enabled === undefined ? {} : { search_cjk_mode: enabled };
}

describe('SearchManager min search length', () => {
  it('defaults to the standard minimum when CJK mode is off', () => {
    setCjkMode(false);

    expect(SearchManager.isCjkMode()).toBe(false);
    expect(SearchManager.minSearchLength()).toBe(SearchManager.MIN_SEARCH_LEN);
    expect(SearchManager.minSearchLength()).toBe(3);
  });

  it('defaults to the standard minimum when the setting is absent', () => {
    setCjkMode(undefined);

    expect(SearchManager.isCjkMode()).toBe(false);
    expect(SearchManager.minSearchLength()).toBe(3);
  });

  it('drops to 1 when CJK mode is on', () => {
    setCjkMode(true);

    expect(SearchManager.isCjkMode()).toBe(true);
    expect(SearchManager.minSearchLength()).toBe(1);
  });
});
