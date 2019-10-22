import compat from '../common/compat';

import Forum from './Forum';

import IndexPage from './components/IndexPage';

export default Object.assign(compat, {
    'components/IndexPage': IndexPage,
    Forum: Forum,
}) as any;
