import compat from '../common/compat';

import Forum from './Forum';

import Layout from './components/Layout';
import IndexPage from './components/IndexPage';

export default Object.assign(compat, {
    'components/Layout': Layout,
    'components/IndexPage': IndexPage,
    Forum: Forum,
}) as any;
