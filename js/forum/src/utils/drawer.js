export default class Drawer {
  hide() {
    $('body').removeClass('drawer-open');
  }

  show() {
    $('body').addClass('drawer-open');
  }

  toggle() {
    $('body').toggleClass('drawer-open');
  }
}
