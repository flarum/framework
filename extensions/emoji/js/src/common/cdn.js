import twemoji from 'twemoji';

export const version = /([0-9]+).[0-9]+.[0-9]+/g.exec(twemoji.base)[1];

export default function () {
  return app.forum.attribute('flarum-emoji.cdn')
    .replace('[version]', version)
};
