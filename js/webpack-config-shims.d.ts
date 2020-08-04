import * as Mithril from 'mithril';
import Stream from 'mithril/stream';

import * as _dayjs from 'dayjs';

interface m extends Mithril.Static {
    prop: typeof Stream;
}

declare global {
    const m: m;
    const dayjs: typeof _dayjs;
}

export as namespace Mithril;

export {};