import HashMentionFormat from '../../../../src/forum/mentionables/formats/HashMentionFormat';

describe('HashMentionFormat', () => {
  const format = new HashMentionFormat();

  // Joiners spell words in Persian and Indic slugs, so the autocomplete searches past them.
  test('searches a typed slug as a whole, joiners and spacing marks included', () => {
    expect(format.queryFromTyped('بی‌بی‌سی-فارسی')).toBe('بی‌بی‌سی-فارسی');
    expect(format.queryFromTyped('ශ්‍රී-ලංකා')).toBe('ශ්‍රී-ලංකා');
    expect(format.queryFromTyped('हिन्दी')).toBe('हिन्दी');
    expect(format.queryFromTyped('tecnologia')).toBe('tecnologia');
  });

  test('stops searching at a space', () => {
    expect(format.queryFromTyped('a b')).toBeNull();
  });
});
