import twemoji from 'twemoji';

export const version = /([0-9]+).[0-9]+.[0-9]+/g.exec(twemoji.base)[1];

export default `https://cdn.jsdelivr.net/gh/twitter/twemoji@${version}/assets/`;
