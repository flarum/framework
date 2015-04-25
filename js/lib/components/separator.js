import Component from 'flarum/component';

/**

 */
class Separator extends Component {
  view() {
    return m('span');
  }
}

Separator.wrapperClass = 'divider';

export default Separator;
