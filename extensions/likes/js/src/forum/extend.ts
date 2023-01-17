import Extend from 'flarum/common/extenders';
import LikesUserPage from './components/LikesUserPage';

export default [new Extend.Routes().add('user.likes', '/u/:username/likes', LikesUserPage)];
