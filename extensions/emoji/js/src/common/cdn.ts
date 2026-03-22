import twemoji from 'twemoji';

export const version = /([0-9]+)\.[0-9]+\.[0-9]+/g.exec((twemoji as any).base)![1];

export default function (): string {
  return app.forum.attribute<string>('flarum-emoji.cdn').replace('[version]', version);
}
