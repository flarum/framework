import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import app from '../../../../src/forum/app';
import GambitsAutocomplete from '../../../../src/common/utils/GambitsAutocomplete';
import { GambitType } from '../../../../src/common/query/IGambit';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

describe('GambitsAutocomplete', () => {
  let changedValue = '';
  let afterSuggestValue = '';
  let selectionStart = 0;

  function createAutocomplete(resource = 'discussions') {
    changedValue = '';
    afterSuggestValue = '';

    const $input = {
      prop: (name: string) => (name === 'selectionStart' ? selectionStart : undefined),
      focus: jest.fn(),
      setSelectionRange: jest.fn(),
      0: {
        focus: jest.fn(),
        setSelectionRange: jest.fn(),
        selectionStart: selectionStart,
      },
    };

    return new GambitsAutocomplete(
      resource,
      () => $input as any,
      (val: string) => {
        changedValue = val;
      },
      (val: string) => {
        afterSuggestValue = val;
      }
    );
  }

  function extractText(node: any): string {
    if (!node) return '';
    if (typeof node === 'string' || typeof node === 'number') return String(node);
    if (Array.isArray(node)) return node.map(extractText).join('');
    if (node.children) return extractText(node.children);
    if (node.text) return String(node.text);
    return '';
  }

  function findChildByClass(vnode: any, className: string): any {
    if (!vnode) return null;
    const cls = vnode.attrs?.className || vnode.attrs?.class || '';
    if (typeof cls === 'string' && cls.split(' ').includes(className)) {
      return vnode;
    }
    const children = Array.isArray(vnode.children) ? vnode.children : vnode.children ? [vnode.children] : [];
    for (const child of children) {
      if (child && typeof child === 'object') {
        const found = findChildByClass(child, className);
        if (found) return found;
      }
    }
    return null;
  }

  function extractSuggestions(vnodes: any[]) {
    return vnodes.map((vnode) => {
      const button = findChildByClass(vnode, 'Button--ua-reset');
      const keySpan = findChildByClass(vnode, 'GambitsAutocomplete-gambit-key');
      const valueSpan = findChildByClass(vnode, 'GambitsAutocomplete-gambit-value');
      const actionsSpan = findChildByClass(vnode, 'GambitsAutocomplete-gambit-actions');

      const actionChildren = Array.isArray(actionsSpan?.children) ? actionsSpan.children : [];
      const actionButtons = actionChildren.filter((c: any) => c && typeof c === 'object');
      const plusButton = actionButtons.find((b: any) => b?.attrs?.icon?.includes('plus'));
      const minusButton = actionButtons.find((b: any) => b?.attrs?.icon?.includes('minus'));

      return {
        key: extractText(keySpan),
        value: valueSpan ? extractText(valueSpan) : null,
        click: () => button?.attrs?.onclick?.(),
        clickPlus: () => plusButton?.attrs?.onclick?.(),
        clickMinus: () => minusButton?.attrs?.onclick?.(),
      };
    });
  }

  beforeEach(() => {
    app.search.gambits.for = jest.fn().mockImplementation((resource: string) => {
      if (resource !== 'discussions') return [];
      return [
        {
          type: GambitType.Grouped,
          suggestion: () => ({ group: 'is', key: ['unread', 'hidden', 'locked', 'sticky'] }),
          enabled: () => true,
        },
        {
          type: GambitType.KeyValue,
          suggestion: () => ({ key: 'author', hint: 'username' }),
          enabled: () => true,
        },
      ];
    });
  });

  it('lists all top-level gambits when query is empty', () => {
    selectionStart = 0;
    const ac = createAutocomplete();
    const suggestions = extractSuggestions(ac.suggestions(''));

    expect(suggestions).toHaveLength(2);
    expect(suggestions[0].key).toBe('is:');
    expect(suggestions[0].value).toBe('unread, hidden, locked, sticky');
    expect(suggestions[1].key).toBe('author:');
    expect(suggestions[1].value).toBe('username');
  });

  it('filters top-level gambits by typed substring', () => {
    selectionStart = 3;
    const ac = createAutocomplete();
    const suggestions = extractSuggestions(ac.suggestions('aut'));

    expect(suggestions).toHaveLength(1);
    expect(suggestions[0].key).toBe('author:');
  });

  it('does not crash when query contains regex special characters', () => {
    selectionStart = 1;
    const ac = createAutocomplete();

    expect(() => ac.suggestions('[')).not.toThrow();
    expect(() => ac.suggestions('(')).not.toThrow();
    expect(() => ac.suggestions('*')).not.toThrow();
    expect(() => ac.suggestions('+')).not.toThrow();
    expect(() => ac.suggestions('\\')).not.toThrow();
  });

  it('lists grouped suggestions when typing group with colon', () => {
    selectionStart = 3;
    const ac = createAutocomplete();
    const suggestions = extractSuggestions(ac.suggestions('is:'));

    expect(suggestions).toHaveLength(4);
    expect(suggestions.map((s) => s.key)).toEqual(['unread', 'hidden', 'locked', 'sticky']);
  });

  it('continues filtering grouped suggestions when typing characters after colon', () => {
    selectionStart = 5;
    const ac = createAutocomplete();
    const suggestions = extractSuggestions(ac.suggestions('is:un'));

    expect(suggestions).toHaveLength(1);
    expect(suggestions[0].key).toBe('unread');
  });

  it('filters grouped suggestions with negative token', () => {
    selectionStart = 6;
    const ac = createAutocomplete();
    const suggestions = extractSuggestions(ac.suggestions('-is:lo'));

    expect(suggestions).toHaveLength(1);
    expect(suggestions[0].key).toBe('locked');
  });

  it('replaces query accurately without duplicating characters when selecting filtered suggestion', () => {
    selectionStart = 5;
    const ac = createAutocomplete();
    const suggestions = extractSuggestions(ac.suggestions('is:un'));

    expect(suggestions).toHaveLength(1);
    suggestions[0].click();

    expect(changedValue).toBe('is:unread');
    expect(afterSuggestValue).toBe('is:unread');
  });

  it('replaces negative query accurately without duplicating characters', () => {
    selectionStart = 6;
    const ac = createAutocomplete();
    const suggestions = extractSuggestions(ac.suggestions('-is:un'));

    expect(suggestions).toHaveLength(1);
    suggestions[0].click();

    expect(changedValue).toBe('-is:unread');
    expect(afterSuggestValue).toBe('-is:unread');
  });

  it('replaces token within a larger query string at the cursor', () => {
    const query = 'hello is:un world';
    selectionStart = 11; // End of 'is:un'
    const ac = createAutocomplete();
    const suggestions = extractSuggestions(ac.suggestions(query));

    expect(suggestions).toHaveLength(1);
    suggestions[0].click();

    expect(changedValue).toBe('hello is:unread world');
    expect(afterSuggestValue).toBe('hello is:unread world');
  });

  it('replaces top-level gambit and handles negation actions without double minus', () => {
    // Regular selection
    selectionStart = 2;
    let ac = createAutocomplete();
    let suggestions = extractSuggestions(ac.suggestions('is'));
    suggestions[0].click();
    expect(changedValue).toBe('is:');

    // Click minus button on positive token
    selectionStart = 2;
    ac = createAutocomplete();
    suggestions = extractSuggestions(ac.suggestions('is'));
    suggestions[0].clickMinus();
    expect(changedValue).toBe('-is:');

    // Click minus button on already negative token (no double minus)
    selectionStart = 3;
    ac = createAutocomplete();
    suggestions = extractSuggestions(ac.suggestions('-is'));
    suggestions[0].clickMinus();
    expect(changedValue).toBe('-is:');

    // Click plus button on negative token (removes minus)
    selectionStart = 3;
    ac = createAutocomplete();
    suggestions = extractSuggestions(ac.suggestions('-is'));
    suggestions[0].clickPlus();
    expect(changedValue).toBe('is:');
  });

  it('returns empty suggestions for non-grouped gambit after colon', () => {
    selectionStart = 11;
    const ac = createAutocomplete();
    const suggestions = ac.suggestions('author:john');

    expect(suggestions).toHaveLength(0);
  });

  it('does not suggest when gambit key is already fully and uniquely typed', () => {
    selectionStart = 9;
    const ac = createAutocomplete();
    const suggestions = ac.suggestions('is:unread');

    expect(suggestions).toHaveLength(0);
  });

  it('handles case-insensitive group keys like IS:un', () => {
    selectionStart = 5;
    const ac = createAutocomplete();
    const suggestions = extractSuggestions(ac.suggestions('IS:un'));

    expect(suggestions).toHaveLength(1);
    expect(suggestions[0].key).toBe('unread');
  });

  it('cleanly replaces entire token even when cursor is in the middle of a word', () => {
    // query is 'is:unold extra', but cursor is after 'is:un' (index 5)
    const query = 'is:unold extra';
    selectionStart = 5;
    const ac = createAutocomplete();
    const suggestions = extractSuggestions(ac.suggestions(query));

    expect(suggestions).toHaveLength(1);
    expect(suggestions[0].key).toBe('unread');
    suggestions[0].click();

    expect(changedValue).toBe('is:unread extra');
    expect(afterSuggestValue).toBe('is:unread extra');
  });
});
