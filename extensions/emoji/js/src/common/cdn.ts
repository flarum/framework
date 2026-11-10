import app from 'flarum/common/app';
import twemoji from '@twemoji/api';

export const version = /([0-9]+)\.[0-9]+\.[0-9]+/g.exec(twemoji.base)![1];

export default function (): string {
  return app.forum.attribute<string>('flarum-emoji.cdn').replace('[version]', version);
}
