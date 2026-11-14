import app from 'flarum/admin/app';

/**
 * The statistics there are, in order: the built-in ones and any that other
 * extensions add with the Statistics extender. Each is labelled by
 * `flarum-statistics.admin.statistics.{name}_heading`.
 */
export default function statisticsEntities(): string[] {
  return app.forum.attribute<string[] | undefined>('statisticsEntities') ?? ['users', 'discussions', 'posts'];
}
