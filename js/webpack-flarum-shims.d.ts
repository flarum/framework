import * as Mithril from 'mithril';
import Stream from 'mithril/stream';

import * as _dayjs from 'dayjs';
import classNames from 'classnames';

interface m extends Mithril.Static {
    prop: typeof Stream;
}

declare global {
    const m: m;
    const dayjs: typeof _dayjs;
    const classNames: typeof classNames;
}

export as namespace Mithril;

export {};
