import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import User from 'flarum/common/models/User';
import UserMention from '../../../../src/forum/mentionables/UserMention';
import AtMentionFormat from '../../../../src/forum/mentionables/formats/AtMentionFormat';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

describe.each([true, false])('UserMention with legacy username format allowed: %s', (allowUsernameMentionFormat) => {
  test.each([
    ['Xhéres', 'Xhéres', 'Xhéres'],
    ['大天犬木', '大天犬木', '大天犬木'],
    ['example@example.com', 'example@example.com', 'example@example.com'],
    ['Hello World', 'Hello World', 'Hello World'],
    ['xheres_ascii', 'Xhéres Nickname', 'Xhéres Nickname'],
    ['normal_user', 'normal_user', 'normal_user'],
    ['quoted_user', 'Say "hello"', 'Say "hello"'],
    ['unsafe_user', 'Bad "#p12 Name', 'Bad _ Name'],
    ['repeated_delimiters', 'A "#1 B "#2 C', 'A _ B _ C'],
    ['group_delimiters', 'A "#g1 B "#g4', 'A _ B _'],
    ['post_delimiters', 'A "#p1 B "#p2', 'A _ B _'],
    ['smart_quotes', 'Bob”#g4', 'Bob”#g4'],
    ['multiline_user', 'Bob\nJones', 'Bob Jones'],
    ['crlf_user', 'Bob\r\nJones', 'Bob Jones'],
    ['unicode_lines', 'Bob\u2028Jones\u2029Smith', 'Bob Jones Smith'],
    ['embedded_mention', 'Bob\n@“Mods”#g4\nX', 'Bob @“Mods”#g4 X'],
  ])('autocompletes %s using its user ID and cleaned display name', (username, displayName, cleanDisplayName) => {
    app.forum.pushAttributes({ allowUsernameMentionFormat });

    const user = new User({ type: 'users', id: '42', attributes: { username, displayName } }, app.store);
    const mention = new UserMention(new AtMentionFormat());

    expect(mention.replacement(user)).toBe(`@"${cleanDisplayName}"#42`);
  });
});
