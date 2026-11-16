import mentionedByTranslationKey from '../../../../src/forum/utils/mentionedByTranslationKey';

const prefix = 'flarum-mentions.forum.post.';

describe('mentionedByTranslationKey', () => {
  test('only you replied', () => {
    expect(mentionedByTranslationKey(true, 1)).toBe(`${prefix}mentioned_by_self_only_text`);
  });

  test('you and one other replied', () => {
    expect(mentionedByTranslationKey(true, 2)).toBe(`${prefix}mentioned_by_self_text`);
  });

  test('you and several others replied, including the "others" entry', () => {
    expect(mentionedByTranslationKey(true, 4)).toBe(`${prefix}mentioned_by_self_text`);
  });

  test('only others replied', () => {
    expect(mentionedByTranslationKey(false, 1)).toBe(`${prefix}mentioned_by_text`);
    expect(mentionedByTranslationKey(false, 3)).toBe(`${prefix}mentioned_by_text`);
  });
});
