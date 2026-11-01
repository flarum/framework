import isOpenedAsApp from '../../../../src/forum/utils/isOpenedAsApp';

function displayMode(standalone: boolean) {
  window.matchMedia = ((query: string) => ({ matches: standalone && query === '(display-mode: standalone)' })) as any;
}

describe('isOpenedAsApp', () => {
  const matchMedia = window.matchMedia;

  afterEach(() => {
    window.matchMedia = matchMedia;
    delete (navigator as any).standalone;
  });

  test('it is when an iPhone or iPad opens the forum from the home screen', () => {
    displayMode(false);
    Object.defineProperty(navigator, 'standalone', { value: true, configurable: true });

    expect(isOpenedAsApp()).toBe(true);
  });

  test('it is when Android opens the forum from the home screen', () => {
    displayMode(true);

    expect(isOpenedAsApp()).toBe(true);
  });

  test('it is not in a browser tab', () => {
    displayMode(false);

    expect(isOpenedAsApp()).toBe(false);
  });

  test('it is not in a browser without media queries', () => {
    (window as any).matchMedia = undefined;

    expect(isOpenedAsApp()).toBe(false);
  });
});
