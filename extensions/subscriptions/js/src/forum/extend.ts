import Extend from 'flarum/common/extenders';
import IndexPage from 'flarum/forum/components/IndexPage';

export default [new Extend.Routes().add('following', '/following', IndexPage)];
