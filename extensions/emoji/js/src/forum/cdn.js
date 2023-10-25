import twemoji from 'twemoji';

export const version = /([0-9]+).[0-9]+.[0-9]+/g.exec(twemoji.base)[1];

const cdn = 'https://cdn.jsdelivr.net/gh/twitter/twemoji@[version]/assets/'

export default function () {
  return (app.forum.attribute('flarum-emoji.cdn') || cdn)
    .replace('[version]', version)
};
