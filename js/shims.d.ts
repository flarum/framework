import * as _dayjs from 'dayjs';

import Forum from './src/forum/Forum';

declare global {
  const dayjs: typeof _dayjs;

  const app: Forum;
}
