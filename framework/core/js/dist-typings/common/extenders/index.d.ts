import Model from './Model';
import PostTypes from './PostTypes';
import Routes from './Routes';
import Store from './Store';
import Search from './Search';
import Notification from './Notification';
import ThemeMode from './ThemeMode';
declare const extenders: {
    Model: typeof Model;
    PostTypes: typeof PostTypes;
    Routes: typeof Routes;
    Store: typeof Store;
    Search: typeof Search;
    Notification: typeof Notification;
    ThemeMode: typeof ThemeMode;
};
export default extenders;
