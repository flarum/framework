import app from 'flarum/forum/app';

/** This extension's forum strings, by the part of the key after `forum.`. */
export const t = (k: string, p?: any): any => app.translator.trans('flarum-ideation.forum.' + k, p);
