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
  let selectionEnd: number | null = null;

  function createAutocomplete(resource = 'discussions') {
    changedValue = '';
    afterSuggestValue = '';

    const effectiveSelectionEnd = selectionEnd !== null ? selectionEnd : selectionStart;
    const $input = {
      prop: (name: string) => {
        if (name === 'selectionStart') return selectionStart;
        if (name === 'selectionEnd') return effectiveSelectionEnd;
        return undefined;
      },
      focus: jest.fn(),
      setSelectionRange: jest.fn(),
      0: {
        focus: jest.fn(),
        setSelectionRange: jest.fn(),
        selectionStart: selectionStart,
        selectionEnd: effectiveSelectionEnd,
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
    selectionStart = 0;
    selectionEnd = null;

    app.search.gambits.for = jest.fn().mockImplementation((resource: string) => {
      if (resource !== 'discussions') return [];
      return [
        {
          type: GambitType.Grouped,
          suggestion: () => ({ group: 'is', key: 'unread' }),
          enabled: () => true,
        },
        {
          type: GambitType.Grouped,
          suggestion: () => ({ group: 'is', key: ['hidden', 'locked', 'sticky'] }),
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

  it('does not delete following word when selecting suggestion with empty fromTyped (e.g. hello ⎸world)', () => {
    const query = 'hello world';
    selectionStart = 6; // Cursor right after 'hello '
    const ac = createAutocomplete();
    const suggestions = extractSuggestions(ac.suggestions(query));

    expect(suggestions).toHaveLength(2);
    expect(suggestions[0].key).toBe('is:');
    suggestions[0].click();

    expect(changedValue).toBe('hello is:world');
    expect(afterSuggestValue).toBe('hello is:world');
  });

  it('does not delete word when selecting suggestion at position 0 with empty fromTyped (e.g. ⎸hello)', () => {
    const query = 'hello';
    selectionStart = 0; // Cursor at start of input before 'hello'
    const ac = createAutocomplete();
    const suggestions = extractSuggestions(ac.suggestions(query));

    expect(suggestions).toHaveLength(2);
    expect(suggestions[0].key).toBe('is:');
    suggestions[0].click();

    expect(changedValue).toBe('is:hello');
    expect(afterSuggestValue).toBe('is:hello');
  });

  it('shows no suggestions when query text is fully selected upon focus (selectionStart 0, selectionEnd length)', () => {
    selectionStart = 0;
    selectionEnd = 3;
    const ac = createAutocomplete();
    const suggestions = ac.suggestions('adm');

    expect(suggestions).toHaveLength(0);
  });

  it('preserves filter value when picking filter name with cursor inside the key (e.g. aut⎸hor:john)', () => {
    const query = 'author:john';
    selectionStart = 3;
    const ac = createAutocomplete();
    const suggestions = extractSuggestions(ac.suggestions(query));

    expect(suggestions).toHaveLength(1);
    expect(suggestions[0].key).toBe('author:');
    suggestions[0].click();

    expect(changedValue).toBe('author:john');
    expect(afterSuggestValue).toBe('author:john');
  });

  describe('extensible KeyValueGambit suggestions', () => {
    beforeEach(() => {
      app.search.gambits.for = jest.fn().mockImplementation((resource: string) => {
        if (resource !== 'discussions') return [];
        return [
          {
            type: GambitType.Grouped,
            suggestion: () => ({ group: 'is', key: ['unread', 'hidden'] }),
            enabled: () => true,
          },
          {
            type: GambitType.KeyValue,
            suggestion: () => ({ key: 'author', hint: 'username' }),
            enabled: () => true,
          },
          {
            type: GambitType.KeyValue,
            suggestion: () => ({
              key: 'priority',
              hint: 'level',
              values: ['low', 'normal', 'urgent'],
            }),
            predicates: false,
            enabled: () => true,
          },
          {
            type: GambitType.KeyValue,
            suggestion: () => ({
              key: 'tag',
              hint: 'slug',
              values: () => [
                { value: 'announcements', label: 'Announcements' },
                { value: 'general', label: 'General' },
                { value: 'help', label: 'Help' },
              ],
            }),
            predicates: true,
            enabled: () => true,
          },
        ];
      });
    });

    it('suggests values for KeyValueGambit with static array values', () => {
      selectionStart = 9;
      const ac = createAutocomplete();
      const suggestions = extractSuggestions(ac.suggestions('priority:'));

      expect(suggestions).toHaveLength(3);
      expect(suggestions.map((s) => s.key)).toEqual(['low', 'normal', 'urgent']);
      expect(suggestions.map((s) => s.value)).toEqual([null, null, null]);

      suggestions[2].click();
      expect(changedValue).toBe('priority:urgent');
      expect(afterSuggestValue).toBe('priority:urgent');
    });

    it('filters KeyValueGambit suggestions by typed prefix after colon', () => {
      selectionStart = 10;
      const ac = createAutocomplete();
      const suggestions = extractSuggestions(ac.suggestions('priority:u'));

      expect(suggestions).toHaveLength(1);
      expect(suggestions[0].key).toBe('urgent');

      suggestions[0].click();
      expect(changedValue).toBe('priority:urgent');
    });

    it('suggests values with labels and supports negation for KeyValueGambit', () => {
      selectionStart = 5;
      const ac = createAutocomplete();
      const suggestions = extractSuggestions(ac.suggestions('-tag:'));

      expect(suggestions).toHaveLength(3);
      expect(suggestions[0].key).toBe('announcements');
      expect(suggestions[0].value).toBe('Announcements');
      expect(suggestions[1].key).toBe('general');
      expect(suggestions[1].value).toBe('General');

      suggestions[0].click();
      expect(changedValue).toBe('-tag:announcements');
    });

    it('supports comma-separated predicates for KeyValueGambit', () => {
      selectionStart = 20; // 'tag:announcements,ge'
      const ac = createAutocomplete();
      const suggestions = extractSuggestions(ac.suggestions('tag:announcements,ge'));

      expect(suggestions).toHaveLength(1);
      expect(suggestions[0].key).toBe('general');

      suggestions[0].click();
      expect(changedValue).toBe('tag:announcements,general');
      expect(afterSuggestValue).toBe('tag:announcements,general');
    });

    it('supports comma-separated predicates with negative prefix', () => {
      selectionStart = 21; // '-tag:announcements,he'
      const ac = createAutocomplete();
      const suggestions = extractSuggestions(ac.suggestions('-tag:announcements,he'));

      expect(suggestions).toHaveLength(1);
      expect(suggestions[0].key).toBe('help');

      suggestions[0].click();
      expect(changedValue).toBe('-tag:announcements,help');
      expect(afterSuggestValue).toBe('-tag:announcements,help');
    });

    it('dismisses autocomplete when KeyValueGambit value is fully and uniquely typed', () => {
      selectionStart = 17; // 'tag:announcements'
      const ac = createAutocomplete();
      const suggestions = ac.suggestions('tag:announcements');

      expect(suggestions).toHaveLength(0);
    });

    it('filters KeyValueGambit by label as well as value', () => {
      selectionStart = 8; // 'tag:Gene'
      const ac = createAutocomplete();
      const suggestions = extractSuggestions(ac.suggestions('tag:Gene'));

      expect(suggestions).toHaveLength(1);
      expect(suggestions[0].key).toBe('general');
    });
  });
});
