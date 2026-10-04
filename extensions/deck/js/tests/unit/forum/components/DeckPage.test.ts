import DeckPage from '../../../../src/forum/components/DeckPage';
import DeckState from '../../../../src/forum/states/DeckState';
import { boot, column, registerTestTypes, setLayout } from '../helpers';

beforeAll(() => {
  boot();
  registerTestTypes();
});

function toolbar(layout: ReturnType<typeof column>[] | null) {
  setLayout(layout);

  const page = new DeckPage() as DeckPage & { deck: DeckState };
  page.deck = new DeckState();

  return page;
}

describe('the toolbar', () => {
  it('offers adding a column, and keeps resetting in its menu', () => {
    const page = toolbar([column('a')]);

    expect(Object.keys(page.actionItems().toObject())).toEqual(['addColumn', 'options']);
    expect(page.optionItems().has('reset')).toBe(true);
  });

  it('only lets a customised deck be reset', () => {
    expect((toolbar(null).optionItems().get('reset') as any).attrs.disabled).toBe(true);
    expect(
      (
        toolbar([column('a')])
          .optionItems()
          .get('reset') as any
      ).attrs.disabled
    ).toBe(false);
  });
});
