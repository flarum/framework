import Dropdown from 'flarum/components/Dropdown';
import ItemList from 'flarum/utils/ItemList';
import icon from 'flarum/helpers/icon';

/**
 * The `IconDropdown` component is the same as a `Dropdown`, except the it
 * houses font awesome icons for selection.
 *
 * ### Props
 *
 * - `selection` - The icon currently selected, required as m.prop
 * - `icons` - The set of icons to display as an option, set as 'all', 'social', or custom array of fa-icons
 */
export default class IconDropdown extends Dropdown {
  static initProps(props) {
    super.initProps(props);
    
    props.className = 'Dropdown--iconselector';
    props.buttonClassName = 'Button Button--iconselector';
    props.menuClassName = 'Dropdown-menu--below';
  }
  
  view() {
    this.props.children = this.items().toArray();

    return super.view();
  }

  getButtonContent() {
    return [
        icon(this.props.selection()),
        this.props.caretIcon ? icon(this.props.caretIcon, {className: 'Button-caret'}) : ''
    ];
  }
  
  items() {
    //Set Defaults
    if (typeof this.props.selection == 'undefined') {
      this.props.selection = m.prop('question');
    } else if (this.props.selection() == '') {
      this.props.selection('question');
    }
    if (typeof this.props.icons == 'undefined') {
      this.props.icons = 'all';
    }

    const items = new ItemList();
    //Preset icon list
    const iconlist = {
        'all':
            ["500px", "adjust", "adn", "amazon", "ambulance", "anchor", "android", "angellist", "apple", "archive", "arrows", "asterisk", "at", "automobile", "backward", "ban", "bank", "barcode", "bars", "bed", "beer", "behance", "bell", "bicycle", "binoculars", "bitbucket", "bitcoin", "bold", "bolt", "bomb", "book", "bookmark", "briefcase", "btc", "bug", "building", "bullhorn", "bullseye", "bus", "buysellads", "cab", "calculator", "calendar", "camera", "car", "cc", "certificate", "chain", "check", "child", "chrome", "circle", "clipboard", "clone", "close", "cloud", "cny", "code", "codepen", "coffee", "cog", "cogs", "columns", "comment", "commenting", "comments", "compass", "compress", "connectdevelop", "contao", "copy", "copyright", "crop", "crosshairs", "css3", "cube", "cubes", "cut", "cutlery", "dashboard", "dashcube", "database", "dedent", "delicious", "desktop", "deviantart", "diamond", "digg", "dollar", "download", "dribbble", "dropbox", "drupal", "edit", "eject", "empire", "envelope", "eraser", "eur", "euro", "exchange", "exclamation", "expand", "expeditedssl", "eye", "eyedropper", "facebook", "fax", "feed", "female", "file", "film", "filter", "fire", "firefox", "flag", "flash", "flask", "flickr", "folder", "font", "fonticons", "forumbee", "forward", "foursquare", "gamepad", "gavel", "gbp", "ge", "gear", "gears", "genderless", "gg", "gift", "git", "github", "gittip", "glass", "globe", "google", "gratipay", "group", "header", "headphones", "heart", "heartbeat", "history", "home", "hotel", "hourglass", "houzz", "html5", "ils", "image", "inbox", "indent", "industry", "info", "inr", "instagram", "institution", "intersex", "ioxhost", "italic", "joomla", "jpy", "jsfiddle", "key", "krw", "language", "laptop", "lastfm", "leaf", "leanpub", "legal", "link", "linkedin", "linux", "list", "lock", "magic", "magnet", "male", "map", "mars", "maxcdn", "meanpath", "medium", "medkit", "mercury", "microphone", "minus", "mobile", "money", "motorcycle", "music", "navicon", "neuter", "odnoklassniki", "opencart", "openid", "opera", "outdent", "pagelines", "paperclip", "paragraph", "paste", "pause", "paw", "paypal", "pencil", "phone", "photo", "pinterest", "plane", "play", "plug", "plus", "print", "qq", "qrcode", "question", "ra", "random", "rebel", "recycle", "reddit", "refresh", "registered", "remove", "renren", "reorder", "repeat", "reply", "retweet", "rmb", "road", "rocket", "rouble", "rss", "rub", "ruble", "rupee", "safari", "save", "scissors", "search", "sellsy", "send", "server", "share", "shekel", "sheqel", "shield", "ship", "shirtsinbulk", "signal", "simplybuilt", "sitemap", "skyatlas", "skype", "slack", "sliders", "slideshare", "sort", "soundcloud", "spinner", "spoon", "spotify", "square", "star", "steam", "stethoscope", "stop", "strikethrough", "stumbleupon", "subscript", "subway", "suitcase", "superscript", "support", "table", "tablet", "tachometer", "tag", "tags", "tasks", "taxi", "television", "terminal", "th", "ticket", "times", "tint", "trademark", "train", "transgender", "trash", "tree", "trello", "tripadvisor", "trophy", "truck", "try", "tty", "tumblr", "tv", "twitch", "twitter", "umbrella", "underline", "undo", "university", "unlink", "unlock", "unsorted", "upload", "usb", "usd", "user", "users", "venus", "viacoin", "vimeo", "vine", "vk", "warning", "wechat", "weibo", "weixin", "whatsapp", "wheelchair", "wifi", "windows", "won", "wordpress", "wrench", "xing", "yahoo", "yc", "yelp", "yen", "youtube"],
        'social':
            ["globe", 'amazon', 'angellist', 'apple', 'behance', 'bitbucket', 'codepen', 'connectdevelop', 'dashcube', 'delicious', 'deviantart', 'digg', 'dribbble', 'dropbox', 'drupal', 'facebook', 'flickr', 'foursquare', 'get-pocket', 'git', 'github', 'github-alt', 'gittip', 'google', 'google-plus', 'google-wallet', 'gratipay', 'hacker-news', 'instagram', 'ioxhost', 'joomla', 'jsfiddle', 'lastfm', 'leanpub', 'linkedin', 'meanpath', 'medium', 'odnoklassniki', 'opencart', 'pagelines', 'paypal', 'pied-piper-alt', 'pinterest-p', 'qq', 'reddit', 'renren', 'sellsy', 'share-alt', 'shirtsinbulk', 'simplybuilt', 'skyatlas', 'skype', 'slack', 'slideshare', 'soundcloud', 'spotify', 'stack-exchange', 'stack-overflow', 'steam', 'stumbleupon', 'tencent-weibo', 'trello', 'tripadvisor', 'tumblr', 'twitch', 'twitter', 'viacoin', 'vimeo', 'vine', 'vk', 'wechat', 'weibo', 'weixin', 'whatsapp', 'wordpress', 'xing', 'y-combinator', 'yelp', 'youtube-play' ],
    };
    //If using custom icon list
    if (typeof iconlist[this.props.icons] == 'undefined') {
      iconlist['custom'] = this.props.icons;
      this.props.icons = 'custom';
    }
    //Add icons to dropdown
    for(const k in iconlist[this.props.icons]) {
      //Highlight Selected
      const highlighted = m.prop(this.props.selection() == iconlist[this.props.icons][k] ? 'highlighted' : '');
      items.add(iconlist[this.props.icons][k],(
        m('div', {onclick: () => {this.props.selection(iconlist[this.props.icons][k]); m.redraw();}, role: "button", href: "#", class: highlighted(), title: iconlist[this.props.icons][k]}, [icon(iconlist[this.props.icons][k])])),
        100
      );
    }
    return items;
  }
}