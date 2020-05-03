import CommonPage from '../../common/components/Page';

export default abstract class Page extends CommonPage {
    oncreate(vnode) {
        super.oncreate(vnode);

        app.drawer.hide();
    }
}
