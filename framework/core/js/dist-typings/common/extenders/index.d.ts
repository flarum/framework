import Model from './Model';
import PostTypes from './PostTypes';
import Routes from './Routes';
import Store from './Store';
declare const extenders: {
    Model: typeof Model;
    PostTypes: typeof PostTypes;
    Routes: typeof Routes;
    Store: typeof Store;
};
export default extenders;
