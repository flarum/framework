(function (global) {
  var babelHelpers = global.babelHelpers = {};
  babelHelpers.typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) {
    return typeof obj;
  } : function (obj) {
    return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj;
  };

  babelHelpers.jsx = function () {
    var REACT_ELEMENT_TYPE = typeof Symbol === "function" && Symbol.for && Symbol.for("react.element") || 0xeac7;
    return function createRawReactElement(type, props, key, children) {
      var defaultProps = type && type.defaultProps;
      var childrenLength = arguments.length - 3;

      if (!props && childrenLength !== 0) {
        props = {};
      }

      if (props && defaultProps) {
        for (var propName in defaultProps) {
          if (props[propName] === void 0) {
            props[propName] = defaultProps[propName];
          }
        }
      } else if (!props) {
        props = defaultProps || {};
      }

      if (childrenLength === 1) {
        props.children = children;
      } else if (childrenLength > 1) {
        var childArray = Array(childrenLength);

        for (var i = 0; i < childrenLength; i++) {
          childArray[i] = arguments[i + 3];
        }

        props.children = childArray;
      }

      return {
        $$typeof: REACT_ELEMENT_TYPE,
        type: type,
        key: key === undefined ? null : '' + key,
        ref: null,
        props: props,
        _owner: null
      };
    };
  }();

  babelHelpers.asyncIterator = function (iterable) {
    if (typeof Symbol === "function") {
      if (Symbol.asyncIterator) {
        var method = iterable[Symbol.asyncIterator];
        if (method != null) return method.call(iterable);
      }

      if (Symbol.iterator) {
        return iterable[Symbol.iterator]();
      }
    }

    throw new TypeError("Object is not async iterable");
  };

  babelHelpers.asyncGenerator = function () {
    function AwaitValue(value) {
      this.value = value;
    }

    function AsyncGenerator(gen) {
      var front, back;

      function send(key, arg) {
        return new Promise(function (resolve, reject) {
          var request = {
            key: key,
            arg: arg,
            resolve: resolve,
            reject: reject,
            next: null
          };

          if (back) {
            back = back.next = request;
          } else {
            front = back = request;
            resume(key, arg);
          }
        });
      }

      function resume(key, arg) {
        try {
          var result = gen[key](arg);
          var value = result.value;

          if (value instanceof AwaitValue) {
            Promise.resolve(value.value).then(function (arg) {
              resume("next", arg);
            }, function (arg) {
              resume("throw", arg);
            });
          } else {
            settle(result.done ? "return" : "normal", result.value);
          }
        } catch (err) {
          settle("throw", err);
        }
      }

      function settle(type, value) {
        switch (type) {
          case "return":
            front.resolve({
              value: value,
              done: true
            });
            break;

          case "throw":
            front.reject(value);
            break;

          default:
            front.resolve({
              value: value,
              done: false
            });
            break;
        }

        front = front.next;

        if (front) {
          resume(front.key, front.arg);
        } else {
          back = null;
        }
      }

      this._invoke = send;

      if (typeof gen.return !== "function") {
        this.return = undefined;
      }
    }

    if (typeof Symbol === "function" && Symbol.asyncIterator) {
      AsyncGenerator.prototype[Symbol.asyncIterator] = function () {
        return this;
      };
    }

    AsyncGenerator.prototype.next = function (arg) {
      return this._invoke("next", arg);
    };

    AsyncGenerator.prototype.throw = function (arg) {
      return this._invoke("throw", arg);
    };

    AsyncGenerator.prototype.return = function (arg) {
      return this._invoke("return", arg);
    };

    return {
      wrap: function (fn) {
        return function () {
          return new AsyncGenerator(fn.apply(this, arguments));
        };
      },
      await: function (value) {
        return new AwaitValue(value);
      }
    };
  }();

  babelHelpers.asyncGeneratorDelegate = function (inner, awaitWrap) {
    var iter = {},
        waiting = false;

    function pump(key, value) {
      waiting = true;
      value = new Promise(function (resolve) {
        resolve(inner[key](value));
      });
      return {
        done: false,
        value: awaitWrap(value)
      };
    }

    ;

    if (typeof Symbol === "function" && Symbol.iterator) {
      iter[Symbol.iterator] = function () {
        return this;
      };
    }

    iter.next = function (value) {
      if (waiting) {
        waiting = false;
        return value;
      }

      return pump("next", value);
    };

    if (typeof inner.throw === "function") {
      iter.throw = function (value) {
        if (waiting) {
          waiting = false;
          throw value;
        }

        return pump("throw", value);
      };
    }

    if (typeof inner.return === "function") {
      iter.return = function (value) {
        return pump("return", value);
      };
    }

    return iter;
  };

  babelHelpers.asyncToGenerator = function (fn) {
    return function () {
      var gen = fn.apply(this, arguments);
      return new Promise(function (resolve, reject) {
        function step(key, arg) {
          try {
            var info = gen[key](arg);
            var value = info.value;
          } catch (error) {
            reject(error);
            return;
          }

          if (info.done) {
            resolve(value);
          } else {
            return Promise.resolve(value).then(function (value) {
              step("next", value);
            }, function (err) {
              step("throw", err);
            });
          }
        }

        return step("next");
      });
    };
  };

  babelHelpers.classCallCheck = function (instance, Constructor) {
    if (!(instance instanceof Constructor)) {
      throw new TypeError("Cannot call a class as a function");
    }
  };

  babelHelpers.createClass = function () {
    function defineProperties(target, props) {
      for (var i = 0; i < props.length; i++) {
        var descriptor = props[i];
        descriptor.enumerable = descriptor.enumerable || false;
        descriptor.configurable = true;
        if ("value" in descriptor) descriptor.writable = true;
        Object.defineProperty(target, descriptor.key, descriptor);
      }
    }

    return function (Constructor, protoProps, staticProps) {
      if (protoProps) defineProperties(Constructor.prototype, protoProps);
      if (staticProps) defineProperties(Constructor, staticProps);
      return Constructor;
    };
  }();

  babelHelpers.defineEnumerableProperties = function (obj, descs) {
    for (var key in descs) {
      var desc = descs[key];
      desc.configurable = desc.enumerable = true;
      if ("value" in desc) desc.writable = true;
      Object.defineProperty(obj, key, desc);
    }

    return obj;
  };

  babelHelpers.defaults = function (obj, defaults) {
    var keys = Object.getOwnPropertyNames(defaults);

    for (var i = 0; i < keys.length; i++) {
      var key = keys[i];
      var value = Object.getOwnPropertyDescriptor(defaults, key);

      if (value && value.configurable && obj[key] === undefined) {
        Object.defineProperty(obj, key, value);
      }
    }

    return obj;
  };

  babelHelpers.defineProperty = function (obj, key, value) {
    if (key in obj) {
      Object.defineProperty(obj, key, {
        value: value,
        enumerable: true,
        configurable: true,
        writable: true
      });
    } else {
      obj[key] = value;
    }

    return obj;
  };

  babelHelpers.extends = Object.assign || function (target) {
    for (var i = 1; i < arguments.length; i++) {
      var source = arguments[i];

      for (var key in source) {
        if (Object.prototype.hasOwnProperty.call(source, key)) {
          target[key] = source[key];
        }
      }
    }

    return target;
  };

  babelHelpers.get = function get(object, property, receiver) {
    if (object === null) object = Function.prototype;
    var desc = Object.getOwnPropertyDescriptor(object, property);

    if (desc === undefined) {
      var parent = Object.getPrototypeOf(object);

      if (parent === null) {
        return undefined;
      } else {
        return get(parent, property, receiver);
      }
    } else if ("value" in desc) {
      return desc.value;
    } else {
      var getter = desc.get;

      if (getter === undefined) {
        return undefined;
      }

      return getter.call(receiver);
    }
  };

  babelHelpers.inherits = function (subClass, superClass) {
    if (typeof superClass !== "function" && superClass !== null) {
      throw new TypeError("Super expression must either be null or a function, not " + typeof superClass);
    }

    subClass.prototype = Object.create(superClass && superClass.prototype, {
      constructor: {
        value: subClass,
        enumerable: false,
        writable: true,
        configurable: true
      }
    });
    if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass;
  };

  babelHelpers.instanceof = function (left, right) {
    if (right != null && typeof Symbol !== "undefined" && right[Symbol.hasInstance]) {
      return right[Symbol.hasInstance](left);
    } else {
      return left instanceof right;
    }
  };

  babelHelpers.interopRequireDefault = function (obj) {
    return obj && obj.__esModule ? obj : {
      default: obj
    };
  };

  babelHelpers.interopRequireWildcard = function (obj) {
    if (obj && obj.__esModule) {
      return obj;
    } else {
      var newObj = {};

      if (obj != null) {
        for (var key in obj) {
          if (Object.prototype.hasOwnProperty.call(obj, key)) newObj[key] = obj[key];
        }
      }

      newObj.default = obj;
      return newObj;
    }
  };

  babelHelpers.newArrowCheck = function (innerThis, boundThis) {
    if (innerThis !== boundThis) {
      throw new TypeError("Cannot instantiate an arrow function");
    }
  };

  babelHelpers.objectDestructuringEmpty = function (obj) {
    if (obj == null) throw new TypeError("Cannot destructure undefined");
  };

  babelHelpers.objectWithoutProperties = function (obj, keys) {
    var target = {};

    for (var i in obj) {
      if (keys.indexOf(i) >= 0) continue;
      if (!Object.prototype.hasOwnProperty.call(obj, i)) continue;
      target[i] = obj[i];
    }

    return target;
  };

  babelHelpers.possibleConstructorReturn = function (self, call) {
    if (!self) {
      throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
    }

    return call && (typeof call === "object" || typeof call === "function") ? call : self;
  };

  babelHelpers.selfGlobal = typeof global === "undefined" ? self : global;

  babelHelpers.set = function set(object, property, value, receiver) {
    var desc = Object.getOwnPropertyDescriptor(object, property);

    if (desc === undefined) {
      var parent = Object.getPrototypeOf(object);

      if (parent !== null) {
        set(parent, property, value, receiver);
      }
    } else if ("value" in desc && desc.writable) {
      desc.value = value;
    } else {
      var setter = desc.set;

      if (setter !== undefined) {
        setter.call(receiver, value);
      }
    }

    return value;
  };

  babelHelpers.slicedToArray = function () {
    function sliceIterator(arr, i) {
      var _arr = [];
      var _n = true;
      var _d = false;
      var _e = undefined;

      try {
        for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) {
          _arr.push(_s.value);

          if (i && _arr.length === i) break;
        }
      } catch (err) {
        _d = true;
        _e = err;
      } finally {
        try {
          if (!_n && _i["return"]) _i["return"]();
        } finally {
          if (_d) throw _e;
        }
      }

      return _arr;
    }

    return function (arr, i) {
      if (Array.isArray(arr)) {
        return arr;
      } else if (Symbol.iterator in Object(arr)) {
        return sliceIterator(arr, i);
      } else {
        throw new TypeError("Invalid attempt to destructure non-iterable instance");
      }
    };
  }();

  babelHelpers.slicedToArrayLoose = function (arr, i) {
    if (Array.isArray(arr)) {
      return arr;
    } else if (Symbol.iterator in Object(arr)) {
      var _arr = [];

      for (var _iterator = arr[Symbol.iterator](), _step; !(_step = _iterator.next()).done;) {
        _arr.push(_step.value);

        if (i && _arr.length === i) break;
      }

      return _arr;
    } else {
      throw new TypeError("Invalid attempt to destructure non-iterable instance");
    }
  };

  babelHelpers.taggedTemplateLiteral = function (strings, raw) {
    return Object.freeze(Object.defineProperties(strings, {
      raw: {
        value: Object.freeze(raw)
      }
    }));
  };

  babelHelpers.taggedTemplateLiteralLoose = function (strings, raw) {
    strings.raw = raw;
    return strings;
  };

  babelHelpers.temporalRef = function (val, name, undef) {
    if (val === undef) {
      throw new ReferenceError(name + " is not defined - temporal dead zone");
    } else {
      return val;
    }
  };

  babelHelpers.temporalUndefined = {};

  babelHelpers.toArray = function (arr) {
    return Array.isArray(arr) ? arr : Array.from(arr);
  };

  babelHelpers.toConsumableArray = function (arr) {
    if (Array.isArray(arr)) {
      for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) arr2[i] = arr[i];

      return arr2;
    } else {
      return Array.from(arr);
    }
  };
})(typeof global === "undefined" ? self : global);;
'use strict';

System.register('flarum/app', ['flarum/ForumApp', 'flarum/initializers/store', 'flarum/initializers/preload', 'flarum/initializers/routes', 'flarum/initializers/components', 'flarum/initializers/humanTime', 'flarum/initializers/boot', 'flarum/initializers/alertEmailConfirmation'], function (_export, _context) {
  "use strict";

  var ForumApp, store, preload, routes, components, humanTime, boot, alertEmailConfirmation, app;
  return {
    setters: [function (_flarumForumApp) {
      ForumApp = _flarumForumApp.default;
    }, function (_flarumInitializersStore) {
      store = _flarumInitializersStore.default;
    }, function (_flarumInitializersPreload) {
      preload = _flarumInitializersPreload.default;
    }, function (_flarumInitializersRoutes) {
      routes = _flarumInitializersRoutes.default;
    }, function (_flarumInitializersComponents) {
      components = _flarumInitializersComponents.default;
    }, function (_flarumInitializersHumanTime) {
      humanTime = _flarumInitializersHumanTime.default;
    }, function (_flarumInitializersBoot) {
      boot = _flarumInitializersBoot.default;
    }, function (_flarumInitializersAlertEmailConfirmation) {
      alertEmailConfirmation = _flarumInitializersAlertEmailConfirmation.default;
    }],
    execute: function () {
      app = new ForumApp();


      app.initializers.add('store', store);
      app.initializers.add('routes', routes);
      app.initializers.add('components', components);
      app.initializers.add('humanTime', humanTime);

      app.initializers.add('preload', preload, -100);
      app.initializers.add('boot', boot, -100);
      app.initializers.add('alertEmailConfirmation', alertEmailConfirmation, -100);

      _export('default', app);
    }
  };
});;
'use strict';

System.register('flarum/App', ['flarum/utils/ItemList', 'flarum/components/Alert', 'flarum/components/Button', 'flarum/components/RequestErrorModal', 'flarum/components/ConfirmPasswordModal', 'flarum/Translator', 'flarum/utils/extract', 'flarum/utils/patchMithril', 'flarum/utils/RequestError', 'flarum/extend'], function (_export, _context) {
  "use strict";

  var ItemList, Alert, Button, RequestErrorModal, ConfirmPasswordModal, Translator, extract, patchMithril, RequestError, extend, App;
  return {
    setters: [function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumComponentsAlert) {
      Alert = _flarumComponentsAlert.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumComponentsRequestErrorModal) {
      RequestErrorModal = _flarumComponentsRequestErrorModal.default;
    }, function (_flarumComponentsConfirmPasswordModal) {
      ConfirmPasswordModal = _flarumComponentsConfirmPasswordModal.default;
    }, function (_flarumTranslator) {
      Translator = _flarumTranslator.default;
    }, function (_flarumUtilsExtract) {
      extract = _flarumUtilsExtract.default;
    }, function (_flarumUtilsPatchMithril) {
      patchMithril = _flarumUtilsPatchMithril.default;
    }, function (_flarumUtilsRequestError) {
      RequestError = _flarumUtilsRequestError.default;
    }, function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }],
    execute: function () {
      App = function () {
        function App() {
          babelHelpers.classCallCheck(this, App);

          patchMithril(window);

          /**
           * The forum model for this application.
           *
           * @type {Forum}
           * @public
           */
          this.forum = null;

          /**
           * A map of routes, keyed by a unique route name. Each route is an object
           * containing the following properties:
           *
           * - `path` The path that the route is accessed at.
           * - `component` The Mithril component to render when this route is active.
           *
           * @example
           * app.routes.discussion = {path: '/d/:id', component: DiscussionPage.component()};
           *
           * @type {Object}
           * @public
           */
          this.routes = {};

          /**
           * An ordered list of initializers to bootstrap the application.
           *
           * @type {ItemList}
           * @public
           */
          this.initializers = new ItemList();

          /**
           * The app's session.
           *
           * @type {Session}
           * @public
           */
          this.session = null;

          /**
           * The app's translator.
           *
           * @type {Translator}
           * @public
           */
          this.translator = new Translator();

          /**
           * The app's data store.
           *
           * @type {Store}
           * @public
           */
          this.store = null;

          /**
           * A local cache that can be used to store data at the application level, so
           * that is persists between different routes.
           *
           * @type {Object}
           * @public
           */
          this.cache = {};

          /**
           * Whether or not the app has been booted.
           *
           * @type {Boolean}
           * @public
           */
          this.booted = false;

          /**
           * An Alert that was shown as a result of an AJAX request error. If present,
           * it will be dismissed on the next successful request.
           *
           * @type {null|Alert}
           * @private
           */
          this.requestError = null;

          this.title = '';
          this.titleCount = 0;
        }

        /**
         * Boot the application by running all of the registered initializers.
         *
         * @public
         */


        babelHelpers.createClass(App, [{
          key: 'boot',
          value: function boot(data) {
            var _this = this;

            this.data = data;

            this.translator.locale = data.locale;

            this.initializers.toArray().forEach(function (initializer) {
              return initializer(_this);
            });
          }
        }, {
          key: 'preloadedDocument',
          value: function preloadedDocument() {
            if (this.data.document) {
              var results = this.store.pushPayload(this.data.document);
              this.data.document = null;

              return results;
            }

            return null;
          }
        }, {
          key: 'setTitle',
          value: function setTitle(title) {
            this.title = title;
            this.updateTitle();
          }
        }, {
          key: 'setTitleCount',
          value: function setTitleCount(count) {
            this.titleCount = count;
            this.updateTitle();
          }
        }, {
          key: 'updateTitle',
          value: function updateTitle() {
            document.title = (this.titleCount ? '(' + this.titleCount + ') ' : '') + (this.title ? this.title + ' - ' : '') + this.forum.attribute('title');
          }
        }, {
          key: 'request',
          value: function request(originalOptions) {
            var _this2 = this;

            var options = babelHelpers.extends({}, originalOptions);

            // Set some default options if they haven't been overridden. We want to
            // authenticate all requests with the session token. We also want all
            // requests to run asynchronously in the background, so that they don't
            // prevent redraws from occurring.
            options.background = options.background || true;

            extend(options, 'config', function (result, xhr) {
              return xhr.setRequestHeader('X-CSRF-Token', _this2.session.csrfToken);
            });

            // If the method is something like PATCH or DELETE, which not all servers
            // and clients support, then we'll send it as a POST request with the
            // intended method specified in the X-HTTP-Method-Override header.
            if (options.method !== 'GET' && options.method !== 'POST') {
              var method = options.method;
              extend(options, 'config', function (result, xhr) {
                return xhr.setRequestHeader('X-HTTP-Method-Override', method);
              });
              options.method = 'POST';
            }

            // When we deserialize JSON data, if for some reason the server has provided
            // a dud response, we don't want the application to crash. We'll show an
            // error message to the user instead.
            options.deserialize = options.deserialize || function (responseText) {
              return responseText;
            };

            options.errorHandler = options.errorHandler || function (error) {
              throw error;
            };

            // When extracting the data from the response, we can check the server
            // response code and show an error message to the user if something's gone
            // awry.
            var original = options.extract;
            options.extract = function (xhr) {
              var responseText = void 0;

              if (original) {
                responseText = original(xhr.responseText);
              } else {
                responseText = xhr.responseText || null;
              }

              var status = xhr.status;

              if (status < 200 || status > 299) {
                throw new RequestError(status, responseText, options, xhr);
              }

              if (xhr.getResponseHeader) {
                var csrfToken = xhr.getResponseHeader('X-CSRF-Token');
                if (csrfToken) app.session.csrfToken = csrfToken;
              }

              try {
                return JSON.parse(responseText);
              } catch (e) {
                throw new RequestError(500, responseText, options, xhr);
              }
            };

            if (this.requestError) this.alerts.dismiss(this.requestError.alert);

            // Now make the request. If it's a failure, inspect the error that was
            // returned and show an alert containing its contents.
            var deferred = m.deferred();

            m.request(options).then(function (response) {
              return deferred.resolve(response);
            }, function (error) {
              _this2.requestError = error;

              var children = void 0;

              switch (error.status) {
                case 422:
                  children = error.response.errors.map(function (error) {
                    return [error.detail, m('br', null)];
                  }).reduce(function (a, b) {
                    return a.concat(b);
                  }, []).slice(0, -1);
                  break;

                case 401:
                case 403:
                  children = app.translator.trans('core.lib.error.permission_denied_message');
                  break;

                case 404:
                case 410:
                  children = app.translator.trans('core.lib.error.not_found_message');
                  break;

                case 429:
                  children = app.translator.trans('core.lib.error.rate_limit_exceeded_message');
                  break;

                default:
                  children = app.translator.trans('core.lib.error.generic_message');
              }

              error.alert = new Alert({
                type: 'error',
                children: children,
                controls: app.forum.attribute('debug') ? [m(
                  Button,
                  { className: 'Button Button--link', onclick: _this2.showDebug.bind(_this2, error) },
                  'Debug'
                )] : undefined
              });

              try {
                options.errorHandler(error);
              } catch (error) {
                _this2.alerts.show(error.alert);
              }

              deferred.reject(error);
            });

            return deferred.promise;
          }
        }, {
          key: 'showDebug',
          value: function showDebug(error) {
            this.alerts.dismiss(this.requestErrorAlert);

            this.modal.show(new RequestErrorModal({ error: error }));
          }
        }, {
          key: 'route',
          value: function route(name) {
            var params = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

            var url = this.routes[name].path.replace(/:([^\/]+)/g, function (m, key) {
              return extract(params, key);
            });
            var queryString = m.route.buildQueryString(params);
            var prefix = m.route.mode === 'pathname' ? app.forum.attribute('basePath') : '';

            return prefix + url + (queryString ? '?' + queryString : '');
          }
        }]);
        return App;
      }();

      _export('default', App);
    }
  };
});;
'use strict';

System.register('flarum/Component', [], function (_export, _context) {
  "use strict";

  var Component;
  return {
    setters: [],
    execute: function () {
      Component = function () {
        /**
         * @param {Object} props
         * @param {Array|Object} children
         * @public
         */
        function Component() {
          var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
          var children = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
          babelHelpers.classCallCheck(this, Component);

          if (children) props.children = children;

          this.constructor.initProps(props);

          /**
           * The properties passed into the component.
           *
           * @type {Object}
           */
          this.props = props;

          /**
           * The root DOM element for the component.
           *
           * @type DOMElement
           * @public
           */
          this.element = null;

          /**
           * Whether or not to retain the component's subtree on redraw.
           *
           * @type {boolean}
           * @public
           */
          this.retain = false;

          this.init();
        }

        /**
         * Called when the component is constructed.
         *
         * @protected
         */


        babelHelpers.createClass(Component, [{
          key: 'init',
          value: function init() {}
        }, {
          key: 'onunload',
          value: function onunload() {}
        }, {
          key: 'render',
          value: function render() {
            var _this = this;

            var vdom = this.retain ? { subtree: 'retain' } : this.view();

            // Override the root element's config attribute with our own function, which
            // will set the component instance's element property to the root DOM
            // element, and then run the component class' config method.
            vdom.attrs = vdom.attrs || {};

            var originalConfig = vdom.attrs.config;

            vdom.attrs.config = function () {
              for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
                args[_key] = arguments[_key];
              }

              _this.element = args[0];
              _this.config.apply(_this, args.slice(1));
              if (originalConfig) originalConfig.apply(_this, args);
            };

            return vdom;
          }
        }, {
          key: '$',
          value: function (_$) {
            function $(_x) {
              return _$.apply(this, arguments);
            }

            $.toString = function () {
              return _$.toString();
            };

            return $;
          }(function (selector) {
            var $element = $(this.element);

            return selector ? $element.find(selector) : $element;
          })
        }, {
          key: 'config',
          value: function config() {}
        }, {
          key: 'view',
          value: function view() {
            throw new Error('Component#view must be implemented by subclass');
          }
        }], [{
          key: 'component',
          value: function component() {
            var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
            var children = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

            var componentProps = babelHelpers.extends({}, props);

            if (children) componentProps.children = children;

            this.initProps(componentProps);

            // Set up a function for Mithril to get the component's view. It will accept
            // the component's controller (which happens to be the component itself, in
            // our case), update its props with the ones supplied, and then render the view.
            var view = function view(component) {
              component.props = componentProps;
              return component.render();
            };

            // Mithril uses this property on the view function to cache component
            // controllers between redraws, thus persisting component state.
            view.$original = this.prototype.view;

            // Our output object consists of a controller constructor + a view function
            // which Mithril will use to instantiate and render the component. We also
            // attach a reference to the props that were passed through and the
            // component's class for reference.
            var output = {
              controller: this.bind(undefined, componentProps),
              view: view,
              props: componentProps,
              component: this
            };

            // If a `key` prop was set, then we'll assume that we want that to actually
            // show up as an attribute on the component object so that Mithril's key
            // algorithm can be applied.
            if (componentProps.key) {
              output.attrs = { key: componentProps.key };
            }

            return output;
          }
        }, {
          key: 'initProps',
          value: function initProps(props) {}
        }]);
        return Component;
      }();

      _export('default', Component);
    }
  };
});;
'use strict';

System.register('flarum/components/Alert', ['flarum/Component', 'flarum/components/Button', 'flarum/helpers/listItems', 'flarum/utils/extract'], function (_export, _context) {
  "use strict";

  var Component, Button, listItems, extract, Alert;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }, function (_flarumUtilsExtract) {
      extract = _flarumUtilsExtract.default;
    }],
    execute: function () {
      Alert = function (_Component) {
        babelHelpers.inherits(Alert, _Component);

        function Alert() {
          babelHelpers.classCallCheck(this, Alert);
          return babelHelpers.possibleConstructorReturn(this, (Alert.__proto__ || Object.getPrototypeOf(Alert)).apply(this, arguments));
        }

        babelHelpers.createClass(Alert, [{
          key: 'view',
          value: function view() {
            var attrs = babelHelpers.extends({}, this.props);

            var type = extract(attrs, 'type');
            attrs.className = 'Alert Alert--' + type + ' ' + (attrs.className || '');

            var children = extract(attrs, 'children');
            var controls = extract(attrs, 'controls') || [];

            // If the alert is meant to be dismissible (which is the case by default),
            // then we will create a dismiss button to append as the final control in
            // the alert.
            var dismissible = extract(attrs, 'dismissible');
            var ondismiss = extract(attrs, 'ondismiss');
            var dismissControl = [];

            if (dismissible || dismissible === undefined) {
              dismissControl.push(m(Button, {
                icon: 'times',
                className: 'Button Button--link Button--icon Alert-dismiss',
                onclick: ondismiss }));
            }

            return m(
              'div',
              attrs,
              m(
                'span',
                { className: 'Alert-body' },
                children
              ),
              m(
                'ul',
                { className: 'Alert-controls' },
                listItems(controls.concat(dismissControl))
              )
            );
          }
        }]);
        return Alert;
      }(Component);

      _export('default', Alert);
    }
  };
});;
'use strict';

System.register('flarum/components/AlertManager', ['flarum/Component', 'flarum/components/Alert'], function (_export, _context) {
  "use strict";

  var Component, Alert, AlertManager;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumComponentsAlert) {
      Alert = _flarumComponentsAlert.default;
    }],
    execute: function () {
      AlertManager = function (_Component) {
        babelHelpers.inherits(AlertManager, _Component);

        function AlertManager() {
          babelHelpers.classCallCheck(this, AlertManager);
          return babelHelpers.possibleConstructorReturn(this, (AlertManager.__proto__ || Object.getPrototypeOf(AlertManager)).apply(this, arguments));
        }

        babelHelpers.createClass(AlertManager, [{
          key: 'init',
          value: function init() {
            /**
             * An array of Alert components which are currently showing.
             *
             * @type {Alert[]}
             * @protected
             */
            this.components = [];
          }
        }, {
          key: 'view',
          value: function view() {
            return m(
              'div',
              { className: 'AlertManager' },
              this.components.map(function (component) {
                return m(
                  'div',
                  { className: 'AlertManager-alert' },
                  component
                );
              })
            );
          }
        }, {
          key: 'config',
          value: function config(isInitialized, context) {
            // Since this component is 'above' the content of the page (that is, it is a
            // part of the global UI that persists between routes), we will flag the DOM
            // to be retained across route changes.
            context.retain = true;
          }
        }, {
          key: 'show',
          value: function show(component) {
            if (!(component instanceof Alert)) {
              throw new Error('The AlertManager component can only show Alert components');
            }

            component.props.ondismiss = this.dismiss.bind(this, component);

            this.components.push(component);
            m.redraw();
          }
        }, {
          key: 'dismiss',
          value: function dismiss(component) {
            var index = this.components.indexOf(component);

            if (index !== -1) {
              this.components.splice(index, 1);
              m.redraw();
            }
          }
        }, {
          key: 'clear',
          value: function clear() {
            this.components = [];
            m.redraw();
          }
        }]);
        return AlertManager;
      }(Component);

      _export('default', AlertManager);
    }
  };
});;
'use strict';

System.register('flarum/components/AvatarEditor', ['flarum/Component', 'flarum/helpers/avatar', 'flarum/helpers/icon', 'flarum/helpers/listItems', 'flarum/utils/ItemList', 'flarum/components/Button', 'flarum/components/LoadingIndicator'], function (_export, _context) {
  "use strict";

  var Component, avatar, icon, listItems, ItemList, Button, LoadingIndicator, AvatarEditor;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumHelpersAvatar) {
      avatar = _flarumHelpersAvatar.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumComponentsLoadingIndicator) {
      LoadingIndicator = _flarumComponentsLoadingIndicator.default;
    }],
    execute: function () {
      AvatarEditor = function (_Component) {
        babelHelpers.inherits(AvatarEditor, _Component);

        function AvatarEditor() {
          babelHelpers.classCallCheck(this, AvatarEditor);
          return babelHelpers.possibleConstructorReturn(this, (AvatarEditor.__proto__ || Object.getPrototypeOf(AvatarEditor)).apply(this, arguments));
        }

        babelHelpers.createClass(AvatarEditor, [{
          key: 'init',
          value: function init() {
            /**
             * Whether or not an avatar upload is in progress.
             *
             * @type {Boolean}
             */
            this.loading = false;
          }
        }, {
          key: 'view',
          value: function view() {
            var user = this.props.user;

            return m(
              'div',
              { className: 'AvatarEditor Dropdown ' + this.props.className + (this.loading ? ' loading' : '') },
              avatar(user),
              m(
                'a',
                { className: user.avatarUrl() ? "Dropdown-toggle" : "Dropdown-toggle AvatarEditor--noAvatar",
                  title: app.translator.trans('core.forum.user.avatar_upload_tooltip'),
                  'data-toggle': 'dropdown',
                  onclick: this.quickUpload.bind(this) },
                this.loading ? LoadingIndicator.component() : user.avatarUrl() ? icon('pencil') : icon('plus-circle')
              ),
              m(
                'ul',
                { className: 'Dropdown-menu Menu' },
                listItems(this.controlItems().toArray())
              )
            );
          }
        }, {
          key: 'controlItems',
          value: function controlItems() {
            var items = new ItemList();

            items.add('upload', Button.component({
              icon: 'upload',
              children: app.translator.trans('core.forum.user.avatar_upload_button'),
              onclick: this.upload.bind(this)
            }));

            items.add('remove', Button.component({
              icon: 'times',
              children: app.translator.trans('core.forum.user.avatar_remove_button'),
              onclick: this.remove.bind(this)
            }));

            return items;
          }
        }, {
          key: 'quickUpload',
          value: function quickUpload(e) {
            if (!this.props.user.avatarUrl()) {
              e.preventDefault();
              e.stopPropagation();
              this.upload();
            }
          }
        }, {
          key: 'upload',
          value: function upload() {
            var _this2 = this;

            if (this.loading) return;

            // Create a hidden HTML input element and click on it so the user can select
            // an avatar file. Once they have, we will upload it via the API.
            var user = this.props.user;
            var $input = $('<input type="file">');

            $input.appendTo('body').hide().click().on('change', function (e) {
              var data = new FormData();
              data.append('avatar', $(e.target)[0].files[0]);

              _this2.loading = true;
              m.redraw();

              app.request({
                method: 'POST',
                url: app.forum.attribute('apiUrl') + '/users/' + user.id() + '/avatar',
                serialize: function serialize(raw) {
                  return raw;
                },
                data: data
              }).then(_this2.success.bind(_this2), _this2.failure.bind(_this2));
            });
          }
        }, {
          key: 'remove',
          value: function remove() {
            var user = this.props.user;

            this.loading = true;
            m.redraw();

            app.request({
              method: 'DELETE',
              url: app.forum.attribute('apiUrl') + '/users/' + user.id() + '/avatar'
            }).then(this.success.bind(this), this.failure.bind(this));
          }
        }, {
          key: 'success',
          value: function success(response) {
            app.store.pushPayload(response);
            delete this.props.user.avatarColor;

            this.loading = false;
            m.redraw();
          }
        }, {
          key: 'failure',
          value: function failure(response) {
            this.loading = false;
            m.redraw();
          }
        }], [{
          key: 'initProps',
          value: function initProps(props) {
            babelHelpers.get(AvatarEditor.__proto__ || Object.getPrototypeOf(AvatarEditor), 'initProps', this).call(this, props);

            props.className = props.className || '';
          }
        }]);
        return AvatarEditor;
      }(Component);

      _export('default', AvatarEditor);
    }
  };
});;
'use strict';

System.register('flarum/components/Badge', ['flarum/Component', 'flarum/helpers/icon', 'flarum/utils/extract'], function (_export, _context) {
  "use strict";

  var Component, icon, extract, Badge;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }, function (_flarumUtilsExtract) {
      extract = _flarumUtilsExtract.default;
    }],
    execute: function () {
      Badge = function (_Component) {
        babelHelpers.inherits(Badge, _Component);

        function Badge() {
          babelHelpers.classCallCheck(this, Badge);
          return babelHelpers.possibleConstructorReturn(this, (Badge.__proto__ || Object.getPrototypeOf(Badge)).apply(this, arguments));
        }

        babelHelpers.createClass(Badge, [{
          key: 'view',
          value: function view() {
            var attrs = babelHelpers.extends({}, this.props);
            var type = extract(attrs, 'type');
            var iconName = extract(attrs, 'icon');

            attrs.className = 'Badge ' + (type ? 'Badge--' + type : '') + ' ' + (attrs.className || '');
            attrs.title = extract(attrs, 'label') || '';

            return m(
              'span',
              attrs,
              iconName ? icon(iconName, { className: 'Badge-icon' }) : m.trust('&nbsp;')
            );
          }
        }, {
          key: 'config',
          value: function config(isInitialized) {
            if (isInitialized) return;

            if (this.props.label) this.$().tooltip({ container: 'body' });
          }
        }]);
        return Badge;
      }(Component);

      _export('default', Badge);
    }
  };
});;
'use strict';

System.register('flarum/components/Button', ['flarum/Component', 'flarum/helpers/icon', 'flarum/utils/extract', 'flarum/utils/extractText', 'flarum/components/LoadingIndicator'], function (_export, _context) {
  "use strict";

  var Component, icon, extract, extractText, LoadingIndicator, Button;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }, function (_flarumUtilsExtract) {
      extract = _flarumUtilsExtract.default;
    }, function (_flarumUtilsExtractText) {
      extractText = _flarumUtilsExtractText.default;
    }, function (_flarumComponentsLoadingIndicator) {
      LoadingIndicator = _flarumComponentsLoadingIndicator.default;
    }],
    execute: function () {
      Button = function (_Component) {
        babelHelpers.inherits(Button, _Component);

        function Button() {
          babelHelpers.classCallCheck(this, Button);
          return babelHelpers.possibleConstructorReturn(this, (Button.__proto__ || Object.getPrototypeOf(Button)).apply(this, arguments));
        }

        babelHelpers.createClass(Button, [{
          key: 'view',
          value: function view() {
            var attrs = babelHelpers.extends({}, this.props);

            delete attrs.children;

            attrs.className = attrs.className || '';
            attrs.type = attrs.type || 'button';

            // If nothing else is provided, we use the textual button content as tooltip
            if (!attrs.title && this.props.children) {
              attrs.title = extractText(this.props.children);
            }

            var iconName = extract(attrs, 'icon');
            if (iconName) attrs.className += ' hasIcon';

            var loading = extract(attrs, 'loading');
            if (attrs.disabled || loading) {
              attrs.className += ' disabled' + (loading ? ' loading' : '');
              delete attrs.onclick;
            }

            return m(
              'button',
              attrs,
              this.getButtonContent()
            );
          }
        }, {
          key: 'getButtonContent',
          value: function getButtonContent() {
            var iconName = this.props.icon;

            return [iconName && iconName !== true ? icon(iconName, { className: 'Button-icon' }) : '', this.props.children ? m(
              'span',
              { className: 'Button-label' },
              this.props.children
            ) : '', this.props.loading ? LoadingIndicator.component({ size: 'tiny', className: 'LoadingIndicator--inline' }) : ''];
          }
        }]);
        return Button;
      }(Component);

      _export('default', Button);
    }
  };
});;
'use strict';

System.register('flarum/components/ChangeEmailModal', ['flarum/components/Modal', 'flarum/components/Button'], function (_export, _context) {
  "use strict";

  var Modal, Button, ChangeEmailModal;
  return {
    setters: [function (_flarumComponentsModal) {
      Modal = _flarumComponentsModal.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }],
    execute: function () {
      ChangeEmailModal = function (_Modal) {
        babelHelpers.inherits(ChangeEmailModal, _Modal);

        function ChangeEmailModal() {
          babelHelpers.classCallCheck(this, ChangeEmailModal);
          return babelHelpers.possibleConstructorReturn(this, (ChangeEmailModal.__proto__ || Object.getPrototypeOf(ChangeEmailModal)).apply(this, arguments));
        }

        babelHelpers.createClass(ChangeEmailModal, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(ChangeEmailModal.prototype.__proto__ || Object.getPrototypeOf(ChangeEmailModal.prototype), 'init', this).call(this);

            /**
             * Whether or not the email has been changed successfully.
             *
             * @type {Boolean}
             */
            this.success = false;

            /**
             * The value of the email input.
             *
             * @type {function}
             */
            this.email = m.prop(app.session.user.email());

            /**
             * The value of the password input.
             *
             * @type {function}
             */
            this.password = m.prop('');
          }
        }, {
          key: 'className',
          value: function className() {
            return 'ChangeEmailModal Modal--small';
          }
        }, {
          key: 'title',
          value: function title() {
            return app.translator.trans('core.forum.change_email.title');
          }
        }, {
          key: 'content',
          value: function content() {
            if (this.success) {
              return m(
                'div',
                { className: 'Modal-body' },
                m(
                  'div',
                  { className: 'Form Form--centered' },
                  m(
                    'p',
                    { className: 'helpText' },
                    app.translator.trans('core.forum.change_email.confirmation_message', { email: m(
                        'strong',
                        null,
                        this.email()
                      ) })
                  ),
                  m(
                    'div',
                    { className: 'Form-group' },
                    m(
                      Button,
                      { className: 'Button Button--primary Button--block', onclick: this.hide.bind(this) },
                      app.translator.trans('core.forum.change_email.dismiss_button')
                    )
                  )
                )
              );
            }

            return m(
              'div',
              { className: 'Modal-body' },
              m(
                'div',
                { className: 'Form Form--centered' },
                m(
                  'div',
                  { className: 'Form-group' },
                  m('input', { type: 'email', name: 'email', className: 'FormControl',
                    placeholder: app.session.user.email(),
                    bidi: this.email,
                    disabled: this.loading })
                ),
                m(
                  'div',
                  { className: 'Form-group' },
                  m('input', { type: 'password', name: 'password', className: 'FormControl',
                    placeholder: app.translator.trans('core.forum.change_email.confirm_password_placeholder'),
                    bidi: this.password,
                    disabled: this.loading })
                ),
                m(
                  'div',
                  { className: 'Form-group' },
                  Button.component({
                    className: 'Button Button--primary Button--block',
                    type: 'submit',
                    loading: this.loading,
                    children: app.translator.trans('core.forum.change_email.submit_button')
                  })
                )
              )
            );
          }
        }, {
          key: 'onsubmit',
          value: function onsubmit(e) {
            var _this2 = this;

            e.preventDefault();

            // If the user hasn't actually entered a different email address, we don't
            // need to do anything. Woot!
            if (this.email() === app.session.user.email()) {
              this.hide();
              return;
            }

            var oldEmail = app.session.user.email();

            this.loading = true;

            app.session.user.save({ email: this.email() }, {
              errorHandler: this.onerror.bind(this),
              meta: { password: this.password() }
            }).then(function () {
              return _this2.success = true;
            }).catch(function () {}).then(this.loaded.bind(this));
          }
        }, {
          key: 'onerror',
          value: function onerror(error) {
            if (error.status === 401) {
              error.alert.props.children = app.translator.trans('core.forum.change_email.incorrect_password_message');
            }

            babelHelpers.get(ChangeEmailModal.prototype.__proto__ || Object.getPrototypeOf(ChangeEmailModal.prototype), 'onerror', this).call(this, error);
          }
        }]);
        return ChangeEmailModal;
      }(Modal);

      _export('default', ChangeEmailModal);
    }
  };
});;
'use strict';

System.register('flarum/components/ChangePasswordModal', ['flarum/components/Modal', 'flarum/components/Button'], function (_export, _context) {
  "use strict";

  var Modal, Button, ChangePasswordModal;
  return {
    setters: [function (_flarumComponentsModal) {
      Modal = _flarumComponentsModal.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }],
    execute: function () {
      ChangePasswordModal = function (_Modal) {
        babelHelpers.inherits(ChangePasswordModal, _Modal);

        function ChangePasswordModal() {
          babelHelpers.classCallCheck(this, ChangePasswordModal);
          return babelHelpers.possibleConstructorReturn(this, (ChangePasswordModal.__proto__ || Object.getPrototypeOf(ChangePasswordModal)).apply(this, arguments));
        }

        babelHelpers.createClass(ChangePasswordModal, [{
          key: 'className',
          value: function className() {
            return 'ChangePasswordModal Modal--small';
          }
        }, {
          key: 'title',
          value: function title() {
            return app.translator.trans('core.forum.change_password.title');
          }
        }, {
          key: 'content',
          value: function content() {
            return m(
              'div',
              { className: 'Modal-body' },
              m(
                'div',
                { className: 'Form Form--centered' },
                m(
                  'p',
                  { className: 'helpText' },
                  app.translator.trans('core.forum.change_password.text')
                ),
                m(
                  'div',
                  { className: 'Form-group' },
                  Button.component({
                    className: 'Button Button--primary Button--block',
                    type: 'submit',
                    loading: this.loading,
                    children: app.translator.trans('core.forum.change_password.send_button')
                  })
                )
              )
            );
          }
        }, {
          key: 'onsubmit',
          value: function onsubmit(e) {
            e.preventDefault();

            this.loading = true;

            app.request({
              method: 'POST',
              url: app.forum.attribute('apiUrl') + '/forgot',
              data: { email: app.session.user.email() }
            }).then(this.hide.bind(this), this.loaded.bind(this));
          }
        }]);
        return ChangePasswordModal;
      }(Modal);

      _export('default', ChangePasswordModal);
    }
  };
});;
'use strict';

System.register('flarum/components/Checkbox', ['flarum/Component', 'flarum/components/LoadingIndicator', 'flarum/helpers/icon'], function (_export, _context) {
  "use strict";

  var Component, LoadingIndicator, icon, Checkbox;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumComponentsLoadingIndicator) {
      LoadingIndicator = _flarumComponentsLoadingIndicator.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }],
    execute: function () {
      Checkbox = function (_Component) {
        babelHelpers.inherits(Checkbox, _Component);

        function Checkbox() {
          babelHelpers.classCallCheck(this, Checkbox);
          return babelHelpers.possibleConstructorReturn(this, (Checkbox.__proto__ || Object.getPrototypeOf(Checkbox)).apply(this, arguments));
        }

        babelHelpers.createClass(Checkbox, [{
          key: 'init',
          value: function init() {
            /**
             * Whether or not the checkbox's value is in the process of being saved.
             *
             * @type {Boolean}
             * @public
             */
            this.loading = false;
          }
        }, {
          key: 'view',
          value: function view() {
            var className = 'Checkbox ' + (this.props.state ? 'on' : 'off') + ' ' + (this.props.className || '');
            if (this.loading) className += ' loading';
            if (this.props.disabled) className += ' disabled';

            return m(
              'label',
              { className: className },
              m('input', { type: 'checkbox',
                checked: this.props.state,
                disabled: this.props.disabled,
                onchange: m.withAttr('checked', this.onchange.bind(this)) }),
              m(
                'div',
                { className: 'Checkbox-display' },
                this.getDisplay()
              ),
              this.props.children
            );
          }
        }, {
          key: 'getDisplay',
          value: function getDisplay() {
            return this.loading ? LoadingIndicator.component({ size: 'tiny' }) : icon(this.props.state ? 'check' : 'times');
          }
        }, {
          key: 'onchange',
          value: function onchange(checked) {
            if (this.props.onchange) this.props.onchange(checked, this);
          }
        }]);
        return Checkbox;
      }(Component);

      _export('default', Checkbox);
    }
  };
});;
'use strict';

System.register('flarum/components/CommentPost', ['flarum/components/Post', 'flarum/utils/classList', 'flarum/components/PostUser', 'flarum/components/PostMeta', 'flarum/components/PostEdited', 'flarum/components/EditPostComposer', 'flarum/utils/ItemList', 'flarum/helpers/listItems', 'flarum/components/Button'], function (_export, _context) {
  "use strict";

  var Post, classList, PostUser, PostMeta, PostEdited, EditPostComposer, ItemList, listItems, Button, CommentPost;
  return {
    setters: [function (_flarumComponentsPost) {
      Post = _flarumComponentsPost.default;
    }, function (_flarumUtilsClassList) {
      classList = _flarumUtilsClassList.default;
    }, function (_flarumComponentsPostUser) {
      PostUser = _flarumComponentsPostUser.default;
    }, function (_flarumComponentsPostMeta) {
      PostMeta = _flarumComponentsPostMeta.default;
    }, function (_flarumComponentsPostEdited) {
      PostEdited = _flarumComponentsPostEdited.default;
    }, function (_flarumComponentsEditPostComposer) {
      EditPostComposer = _flarumComponentsEditPostComposer.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }],
    execute: function () {
      CommentPost = function (_Post) {
        babelHelpers.inherits(CommentPost, _Post);

        function CommentPost() {
          babelHelpers.classCallCheck(this, CommentPost);
          return babelHelpers.possibleConstructorReturn(this, (CommentPost.__proto__ || Object.getPrototypeOf(CommentPost)).apply(this, arguments));
        }

        babelHelpers.createClass(CommentPost, [{
          key: 'init',
          value: function init() {
            var _this2 = this;

            babelHelpers.get(CommentPost.prototype.__proto__ || Object.getPrototypeOf(CommentPost.prototype), 'init', this).call(this);

            /**
             * If the post has been hidden, then this flag determines whether or not its
             * content has been expanded.
             *
             * @type {Boolean}
             */
            this.revealContent = false;

            // Create an instance of the component that displays the post's author so
            // that we can force the post to rerender when the user card is shown.
            this.postUser = new PostUser({ post: this.props.post });
            this.subtree.check(function () {
              return _this2.postUser.cardVisible;
            }, function () {
              return _this2.isEditing();
            });
          }
        }, {
          key: 'content',
          value: function content() {
            // Note: we avoid using JSX for the <ul> below because it results in some
            // weirdness in Mithril.js 0.1.x (see flarum/core#975). This workaround can
            // be reverted when we upgrade to Mithril 1.0.
            return babelHelpers.get(CommentPost.prototype.__proto__ || Object.getPrototypeOf(CommentPost.prototype), 'content', this).call(this).concat([m(
              'header',
              { className: 'Post-header' },
              m('ul', listItems(this.headerItems().toArray()))
            ), m(
              'div',
              { className: 'Post-body' },
              this.isEditing() ? m('div', { className: 'Post-preview', config: this.configPreview.bind(this) }) : m.trust(this.props.post.contentHtml())
            )]);
          }
        }, {
          key: 'config',
          value: function config(isInitialized, context) {
            babelHelpers.get(CommentPost.prototype.__proto__ || Object.getPrototypeOf(CommentPost.prototype), 'config', this).apply(this, arguments);

            var contentHtml = this.isEditing() ? '' : this.props.post.contentHtml();

            // If the post content has changed since the last render, we'll run through
            // all of the <script> tags in the content and evaluate them. This is
            // necessary because TextFormatter outputs them for e.g. syntax highlighting.
            if (context.contentHtml !== contentHtml) {
              this.$('.Post-body script').each(function () {
                eval.call(window, $(this).text());
              });
            }

            context.contentHtml = contentHtml;
          }
        }, {
          key: 'isEditing',
          value: function isEditing() {
            return app.composer.component instanceof EditPostComposer && app.composer.component.props.post === this.props.post;
          }
        }, {
          key: 'attrs',
          value: function attrs() {
            var post = this.props.post;
            var attrs = babelHelpers.get(CommentPost.prototype.__proto__ || Object.getPrototypeOf(CommentPost.prototype), 'attrs', this).call(this);

            attrs.className += ' ' + classList({
              'CommentPost': true,
              'Post--hidden': post.isHidden(),
              'Post--edited': post.isEdited(),
              'revealContent': this.revealContent,
              'editing': this.isEditing()
            });

            return attrs;
          }
        }, {
          key: 'configPreview',
          value: function configPreview(element, isInitialized, context) {
            if (isInitialized) return;

            // Every 50ms, if the composer content has changed, then update the post's
            // body with a preview.
            var preview = void 0;
            var updatePreview = function updatePreview() {
              var content = app.composer.component.content();

              if (preview === content) return;

              preview = content;

              s9e.TextFormatter.preview(preview || '', element);
            };
            updatePreview();

            var updateInterval = setInterval(updatePreview, 50);
            context.onunload = function () {
              return clearInterval(updateInterval);
            };
          }
        }, {
          key: 'toggleContent',
          value: function toggleContent() {
            this.revealContent = !this.revealContent;
          }
        }, {
          key: 'headerItems',
          value: function headerItems() {
            var items = new ItemList();
            var post = this.props.post;
            var props = { post: post };

            items.add('user', this.postUser.render(), 100);
            items.add('meta', PostMeta.component(props));

            if (post.isEdited() && !post.isHidden()) {
              items.add('edited', PostEdited.component(props));
            }

            // If the post is hidden, add a button that allows toggling the visibility
            // of the post's content.
            if (post.isHidden()) {
              items.add('toggle', Button.component({
                className: 'Button Button--default Button--more',
                icon: 'ellipsis-h',
                onclick: this.toggleContent.bind(this)
              }));
            }

            return items;
          }
        }]);
        return CommentPost;
      }(Post);

      _export('default', CommentPost);
    }
  };
});;
'use strict';

System.register('flarum/components/Composer', ['flarum/Component', 'flarum/utils/ItemList', 'flarum/components/ComposerButton', 'flarum/helpers/listItems', 'flarum/utils/classList', 'flarum/utils/computed'], function (_export, _context) {
  "use strict";

  var Component, ItemList, ComposerButton, listItems, classList, computed, Composer;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumComponentsComposerButton) {
      ComposerButton = _flarumComponentsComposerButton.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }, function (_flarumUtilsClassList) {
      classList = _flarumUtilsClassList.default;
    }, function (_flarumUtilsComputed) {
      computed = _flarumUtilsComputed.default;
    }],
    execute: function () {
      Composer = function (_Component) {
        babelHelpers.inherits(Composer, _Component);

        function Composer() {
          babelHelpers.classCallCheck(this, Composer);
          return babelHelpers.possibleConstructorReturn(this, (Composer.__proto__ || Object.getPrototypeOf(Composer)).apply(this, arguments));
        }

        babelHelpers.createClass(Composer, [{
          key: 'init',
          value: function init() {
            /**
             * The composer's current position.
             *
             * @type {Composer.PositionEnum}
             */
            this.position = Composer.PositionEnum.HIDDEN;

            /**
             * The composer's intended height, which can be modified by the user
             * (by dragging the composer handle).
             *
             * @type {Integer}
             */
            this.height = null;

            /**
             * Whether or not the composer currently has focus.
             *
             * @type {Boolean}
             */
            this.active = false;

            /**
             * Computed the composer's current height, based on the intended height, and
             * the composer's current state. This will be applied to the composer's
             * content's DOM element.
             *
             * @return {Integer}
             */
            this.computedHeight = computed('height', 'position', function (height, position) {
              // If the composer is minimized, then we don't want to set a height; we'll
              // let the CSS decide how high it is. If it's fullscreen, then we need to
              // make it as high as the window.
              if (position === Composer.PositionEnum.MINIMIZED) {
                return '';
              } else if (position === Composer.PositionEnum.FULLSCREEN) {
                return $(window).height();
              }

              // Otherwise, if it's normal or hidden, then we use the intended height.
              // We don't let the composer get too small or too big, though.
              return Math.max(200, Math.min(height, $(window).height() - $('#header').outerHeight()));
            });
          }
        }, {
          key: 'view',
          value: function view() {
            var classes = {
              'normal': this.position === Composer.PositionEnum.NORMAL,
              'minimized': this.position === Composer.PositionEnum.MINIMIZED,
              'fullScreen': this.position === Composer.PositionEnum.FULLSCREEN,
              'active': this.active
            };
            classes.visible = classes.normal || classes.minimized || classes.fullScreen;

            // If the composer is minimized, tell the composer's content component that
            // it shouldn't let the user interact with it. Set up a handler so that if
            // the content IS clicked, the composer will be shown.
            if (this.component) this.component.props.disabled = classes.minimized;

            var showIfMinimized = this.position === Composer.PositionEnum.MINIMIZED ? this.show.bind(this) : undefined;

            return m(
              'div',
              { className: 'Composer ' + classList(classes) },
              m('div', { className: 'Composer-handle', config: this.configHandle.bind(this) }),
              m(
                'ul',
                { className: 'Composer-controls' },
                listItems(this.controlItems().toArray())
              ),
              m(
                'div',
                { className: 'Composer-content', onclick: showIfMinimized },
                this.component ? this.component.render() : ''
              )
            );
          }
        }, {
          key: 'config',
          value: function config(isInitialized, context) {
            var _this2 = this;

            var defaultHeight = void 0;

            if (!isInitialized) {
              defaultHeight = this.$().height();
            }

            if (isInitialized) return;

            // Since this component is a part of the global UI that persists between
            // routes, we will flag the DOM to be retained across route changes.
            context.retain = true;

            // Initialize the composer's intended height based on what the user has set
            // it at previously, or otherwise the composer's default height. After that,
            // we'll hide the composer.
            this.height = localStorage.getItem('composerHeight') || defaultHeight;
            this.$().hide().css('bottom', -this.height);

            // Whenever any of the inputs inside the composer are have focus, we want to
            // add a class to the composer to draw attention to it.
            this.$().on('focus blur', ':input', function (e) {
              _this2.active = e.type === 'focusin';
              m.redraw();
            });

            // When the escape key is pressed on any inputs, close the composer.
            this.$().on('keydown', ':input', 'esc', function () {
              return _this2.close();
            });

            // Don't let the user leave the page without first giving the composer's
            // component a chance to scream at the user to make sure they don't
            // unintentionally lose any contnet.
            window.onbeforeunload = function () {
              return _this2.component && _this2.component.preventExit() || undefined;
            };

            var handlers = {};

            $(window).on('resize', handlers.onresize = this.updateHeight.bind(this)).resize();

            $(document).on('mousemove', handlers.onmousemove = this.onmousemove.bind(this)).on('mouseup', handlers.onmouseup = this.onmouseup.bind(this));

            context.onunload = function () {
              $(window).off('resize', handlers.onresize);

              $(document).off('mousemove', handlers.onmousemove).off('mouseup', handlers.onmouseup);
            };
          }
        }, {
          key: 'configHandle',
          value: function configHandle(element, isInitialized) {
            if (isInitialized) return;

            var composer = this;

            $(element).css('cursor', 'row-resize').bind('dragstart mousedown', function (e) {
              return e.preventDefault();
            }).mousedown(function (e) {
              composer.mouseStart = e.clientY;
              composer.heightStart = composer.$().height();
              composer.handle = $(this);
              $('body').css('cursor', 'row-resize');
            });
          }
        }, {
          key: 'onmousemove',
          value: function onmousemove(e) {
            if (!this.handle) return;

            // Work out how much the mouse has been moved, and set the height
            // relative to the old one based on that. Then update the content's
            // height so that it fills the height of the composer, and update the
            // body's padding.
            var deltaPixels = this.mouseStart - e.clientY;
            this.height = this.heightStart + deltaPixels;
            this.updateHeight();

            // Update the body's padding-bottom so that no content on the page will ever
            // get permanently hidden behind the composer. If the user is already
            // scrolled to the bottom of the page, then we will keep them scrolled to
            // the bottom after the padding has been updated.
            var scrollTop = $(window).scrollTop();
            var anchorToBottom = scrollTop > 0 && scrollTop + $(window).height() >= $(document).height();
            this.updateBodyPadding(anchorToBottom);

            localStorage.setItem('composerHeight', this.height);
          }
        }, {
          key: 'onmouseup',
          value: function onmouseup() {
            if (!this.handle) return;

            this.handle = null;
            $('body').css('cursor', '');
          }
        }, {
          key: 'updateHeight',
          value: function updateHeight() {
            var height = this.computedHeight();
            var $flexible = this.$('.Composer-flexible');

            this.$().height(height);

            if ($flexible.length) {
              var headerHeight = $flexible.offset().top - this.$().offset().top;
              var paddingBottom = parseInt($flexible.css('padding-bottom'), 10);
              var footerHeight = this.$('.Composer-footer').outerHeight(true);

              $flexible.height(this.$().outerHeight() - headerHeight - paddingBottom - footerHeight);
            }
          }
        }, {
          key: 'updateBodyPadding',
          value: function updateBodyPadding() {
            var visible = this.position !== Composer.PositionEnum.HIDDEN && this.position !== Composer.PositionEnum.MINIMIZED && this.$().css('position') !== 'absolute';

            var paddingBottom = visible ? this.computedHeight() - parseInt($('#app').css('padding-bottom'), 10) : 0;

            $('#content').css({ paddingBottom: paddingBottom });
          }
        }, {
          key: 'isFullScreen',
          value: function isFullScreen() {
            return this.position === Composer.PositionEnum.FULLSCREEN || this.$().css('position') === 'absolute';
          }
        }, {
          key: 'preventExit',
          value: function preventExit() {
            if (this.component) {
              var preventExit = this.component.preventExit();

              if (preventExit) {
                return !confirm(preventExit);
              }
            }
          }
        }, {
          key: 'load',
          value: function load(component) {
            if (this.preventExit()) return;

            // If we load a similar component into the composer, then Mithril will be
            // able to diff the old/new contents and some DOM-related state from the
            // old composer will remain. To prevent this from happening, we clear the
            // component and force a redraw, so that the new component will be working
            // on a blank slate.
            if (this.component) {
              this.clear();
              m.redraw(true);
            }

            this.component = component;
          }
        }, {
          key: 'clear',
          value: function clear() {
            this.component = null;
          }
        }, {
          key: 'animateToPosition',
          value: function animateToPosition(position) {
            var _this3 = this;

            // Before we redraw the composer to its new state, we need to save the
            // current height of the composer, as well as the page's scroll position, so
            // that we can smoothly transition from the old to the new state.
            var oldPosition = this.position;
            var $composer = this.$().stop(true);
            var oldHeight = $composer.outerHeight();
            var scrollTop = $(window).scrollTop();

            this.position = position;

            m.redraw(true);

            // Now that we've redrawn and the composer's DOM has been updated, we want
            // to update the composer's height. Once we've done that, we'll capture the
            // real value to use as the end point for our animation later on.
            $composer.show();
            this.updateHeight();

            var newHeight = $composer.outerHeight();

            if (oldPosition === Composer.PositionEnum.HIDDEN) {
              $composer.css({ bottom: -newHeight, height: newHeight });
            } else {
              $composer.css({ height: oldHeight });
            }

            $composer.animate({ bottom: 0, height: newHeight }, 'fast', function () {
              return _this3.component.focus();
            });

            this.updateBodyPadding();
            $(window).scrollTop(scrollTop);
          }
        }, {
          key: 'showBackdrop',
          value: function showBackdrop() {
            this.$backdrop = $('<div/>').addClass('composer-backdrop').appendTo('body');
          }
        }, {
          key: 'hideBackdrop',
          value: function hideBackdrop() {
            if (this.$backdrop) this.$backdrop.remove();
          }
        }, {
          key: 'show',
          value: function show() {
            if (this.position === Composer.PositionEnum.NORMAL || this.position === Composer.PositionEnum.FULLSCREEN) {
              return;
            }

            this.animateToPosition(Composer.PositionEnum.NORMAL);

            if (this.isFullScreen()) {
              this.$().css('top', $(window).scrollTop());
              this.showBackdrop();
              this.component.focus();
            }
          }
        }, {
          key: 'hide',
          value: function hide() {
            var _this4 = this;

            var $composer = this.$();

            // Animate the composer sliding down off the bottom edge of the viewport.
            // Only when the animation is completed, update the Composer state flag and
            // other elements on the page.
            $composer.stop(true).animate({ bottom: -$composer.height() }, 'fast', function () {
              _this4.position = Composer.PositionEnum.HIDDEN;
              _this4.clear();
              m.redraw();

              $composer.hide();
              _this4.hideBackdrop();
              _this4.updateBodyPadding();
            });
          }
        }, {
          key: 'close',
          value: function close() {
            if (!this.preventExit()) {
              this.hide();
            }
          }
        }, {
          key: 'minimize',
          value: function minimize() {
            if (this.position === Composer.PositionEnum.HIDDEN) return;

            this.animateToPosition(Composer.PositionEnum.MINIMIZED);

            this.$().css('top', 'auto');
            this.hideBackdrop();
          }
        }, {
          key: 'fullScreen',
          value: function fullScreen() {
            if (this.position !== Composer.PositionEnum.HIDDEN) {
              this.position = Composer.PositionEnum.FULLSCREEN;
              m.redraw();
              this.updateHeight();
              this.component.focus();
            }
          }
        }, {
          key: 'exitFullScreen',
          value: function exitFullScreen() {
            if (this.position === Composer.PositionEnum.FULLSCREEN) {
              this.position = Composer.PositionEnum.NORMAL;
              m.redraw();
              this.updateHeight();
              this.component.focus();
            }
          }
        }, {
          key: 'controlItems',
          value: function controlItems() {
            var items = new ItemList();

            if (this.position === Composer.PositionEnum.FULLSCREEN) {
              items.add('exitFullScreen', ComposerButton.component({
                icon: 'compress',
                title: app.translator.trans('core.forum.composer.exit_full_screen_tooltip'),
                onclick: this.exitFullScreen.bind(this)
              }));
            } else {
              if (this.position !== Composer.PositionEnum.MINIMIZED) {
                items.add('minimize', ComposerButton.component({
                  icon: 'minus minimize',
                  title: app.translator.trans('core.forum.composer.minimize_tooltip'),
                  onclick: this.minimize.bind(this),
                  itemClassName: 'App-backControl'
                }));

                items.add('fullScreen', ComposerButton.component({
                  icon: 'expand',
                  title: app.translator.trans('core.forum.composer.full_screen_tooltip'),
                  onclick: this.fullScreen.bind(this)
                }));
              }

              items.add('close', ComposerButton.component({
                icon: 'times',
                title: app.translator.trans('core.forum.composer.close_tooltip'),
                onclick: this.close.bind(this)
              }));
            }

            return items;
          }
        }]);
        return Composer;
      }(Component);

      Composer.PositionEnum = {
        HIDDEN: 'hidden',
        NORMAL: 'normal',
        MINIMIZED: 'minimized',
        FULLSCREEN: 'fullScreen'
      };

      _export('default', Composer);
    }
  };
});;
'use strict';

System.register('flarum/components/ComposerBody', ['flarum/Component', 'flarum/components/LoadingIndicator', 'flarum/components/TextEditor', 'flarum/helpers/avatar', 'flarum/helpers/listItems', 'flarum/utils/ItemList'], function (_export, _context) {
  "use strict";

  var Component, LoadingIndicator, TextEditor, avatar, listItems, ItemList, ComposerBody;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumComponentsLoadingIndicator) {
      LoadingIndicator = _flarumComponentsLoadingIndicator.default;
    }, function (_flarumComponentsTextEditor) {
      TextEditor = _flarumComponentsTextEditor.default;
    }, function (_flarumHelpersAvatar) {
      avatar = _flarumHelpersAvatar.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }],
    execute: function () {
      ComposerBody = function (_Component) {
        babelHelpers.inherits(ComposerBody, _Component);

        function ComposerBody() {
          babelHelpers.classCallCheck(this, ComposerBody);
          return babelHelpers.possibleConstructorReturn(this, (ComposerBody.__proto__ || Object.getPrototypeOf(ComposerBody)).apply(this, arguments));
        }

        babelHelpers.createClass(ComposerBody, [{
          key: 'init',
          value: function init() {
            /**
             * Whether or not the component is loading.
             *
             * @type {Boolean}
             */
            this.loading = false;

            /**
             * The content of the text editor.
             *
             * @type {Function}
             */
            this.content = m.prop(this.props.originalContent);

            /**
             * The text editor component instance.
             *
             * @type {TextEditor}
             */
            this.editor = new TextEditor({
              submitLabel: this.props.submitLabel,
              placeholder: this.props.placeholder,
              onchange: this.content,
              onsubmit: this.onsubmit.bind(this),
              value: this.content()
            });
          }
        }, {
          key: 'view',
          value: function view() {
            // If the component is loading, we should disable the text editor.
            this.editor.props.disabled = this.loading;

            return m(
              'div',
              { className: 'ComposerBody ' + (this.props.className || '') },
              avatar(this.props.user, { className: 'ComposerBody-avatar' }),
              m(
                'div',
                { className: 'ComposerBody-content' },
                m(
                  'ul',
                  { className: 'ComposerBody-header' },
                  listItems(this.headerItems().toArray())
                ),
                m(
                  'div',
                  { className: 'ComposerBody-editor' },
                  this.editor.render()
                )
              ),
              LoadingIndicator.component({ className: 'ComposerBody-loading' + (this.loading ? ' active' : '') })
            );
          }
        }, {
          key: 'focus',
          value: function focus() {
            this.$(':input:enabled:visible:first').focus();
          }
        }, {
          key: 'preventExit',
          value: function preventExit() {
            var content = this.content();

            return content && content !== this.props.originalContent && this.props.confirmExit;
          }
        }, {
          key: 'headerItems',
          value: function headerItems() {
            return new ItemList();
          }
        }, {
          key: 'onsubmit',
          value: function onsubmit() {}
        }, {
          key: 'loaded',
          value: function loaded() {
            this.loading = false;
            m.redraw();
          }
        }]);
        return ComposerBody;
      }(Component);

      _export('default', ComposerBody);
    }
  };
});;
'use strict';

System.register('flarum/components/ComposerButton', ['flarum/components/Button'], function (_export, _context) {
  "use strict";

  var Button, ComposerButton;
  return {
    setters: [function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }],
    execute: function () {
      ComposerButton = function (_Button) {
        babelHelpers.inherits(ComposerButton, _Button);

        function ComposerButton() {
          babelHelpers.classCallCheck(this, ComposerButton);
          return babelHelpers.possibleConstructorReturn(this, (ComposerButton.__proto__ || Object.getPrototypeOf(ComposerButton)).apply(this, arguments));
        }

        babelHelpers.createClass(ComposerButton, null, [{
          key: 'initProps',
          value: function initProps(props) {
            babelHelpers.get(ComposerButton.__proto__ || Object.getPrototypeOf(ComposerButton), 'initProps', this).call(this, props);

            props.className = props.className || 'Button Button--icon Button--link';
          }
        }]);
        return ComposerButton;
      }(Button);

      _export('default', ComposerButton);
    }
  };
});;
'use strict';

System.register('flarum/components/DiscussionComposer', ['flarum/components/ComposerBody', 'flarum/utils/extractText'], function (_export, _context) {
  "use strict";

  var ComposerBody, extractText, DiscussionComposer;
  return {
    setters: [function (_flarumComponentsComposerBody) {
      ComposerBody = _flarumComponentsComposerBody.default;
    }, function (_flarumUtilsExtractText) {
      extractText = _flarumUtilsExtractText.default;
    }],
    execute: function () {
      DiscussionComposer = function (_ComposerBody) {
        babelHelpers.inherits(DiscussionComposer, _ComposerBody);

        function DiscussionComposer() {
          babelHelpers.classCallCheck(this, DiscussionComposer);
          return babelHelpers.possibleConstructorReturn(this, (DiscussionComposer.__proto__ || Object.getPrototypeOf(DiscussionComposer)).apply(this, arguments));
        }

        babelHelpers.createClass(DiscussionComposer, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(DiscussionComposer.prototype.__proto__ || Object.getPrototypeOf(DiscussionComposer.prototype), 'init', this).call(this);

            /**
             * The value of the title input.
             *
             * @type {Function}
             */
            this.title = m.prop('');
          }
        }, {
          key: 'headerItems',
          value: function headerItems() {
            var items = babelHelpers.get(DiscussionComposer.prototype.__proto__ || Object.getPrototypeOf(DiscussionComposer.prototype), 'headerItems', this).call(this);

            items.add('title', m(
              'h3',
              null,
              app.translator.trans('core.forum.composer_discussion.title')
            ), 100);

            items.add('discussionTitle', m(
              'h3',
              null,
              m('input', { className: 'FormControl',
                value: this.title(),
                oninput: m.withAttr('value', this.title),
                placeholder: this.props.titlePlaceholder,
                disabled: !!this.props.disabled,
                onkeydown: this.onkeydown.bind(this) })
            ));

            return items;
          }
        }, {
          key: 'onkeydown',
          value: function onkeydown(e) {
            if (e.which === 13) {
              // Return
              e.preventDefault();
              this.editor.setSelectionRange(0, 0);
            }

            m.redraw.strategy('none');
          }
        }, {
          key: 'preventExit',
          value: function preventExit() {
            return (this.title() || this.content()) && this.props.confirmExit;
          }
        }, {
          key: 'data',
          value: function data() {
            return {
              title: this.title(),
              content: this.content()
            };
          }
        }, {
          key: 'onsubmit',
          value: function onsubmit() {
            this.loading = true;

            var data = this.data();

            app.store.createRecord('discussions').save(data).then(function (discussion) {
              app.composer.hide();
              app.cache.discussionList.addDiscussion(discussion);
              m.route(app.route.discussion(discussion));
            }, this.loaded.bind(this));
          }
        }], [{
          key: 'initProps',
          value: function initProps(props) {
            babelHelpers.get(DiscussionComposer.__proto__ || Object.getPrototypeOf(DiscussionComposer), 'initProps', this).call(this, props);

            props.placeholder = props.placeholder || extractText(app.translator.trans('core.forum.composer_discussion.body_placeholder'));
            props.submitLabel = props.submitLabel || app.translator.trans('core.forum.composer_discussion.submit_button');
            props.confirmExit = props.confirmExit || extractText(app.translator.trans('core.forum.composer_discussion.discard_confirmation'));
            props.titlePlaceholder = props.titlePlaceholder || extractText(app.translator.trans('core.forum.composer_discussion.title_placeholder'));
            props.className = 'ComposerBody--discussion';
          }
        }]);
        return DiscussionComposer;
      }(ComposerBody);

      _export('default', DiscussionComposer);
    }
  };
});;
'use strict';

System.register('flarum/components/DiscussionHero', ['flarum/Component', 'flarum/utils/ItemList', 'flarum/helpers/listItems'], function (_export, _context) {
  "use strict";

  var Component, ItemList, listItems, DiscussionHero;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }],
    execute: function () {
      DiscussionHero = function (_Component) {
        babelHelpers.inherits(DiscussionHero, _Component);

        function DiscussionHero() {
          babelHelpers.classCallCheck(this, DiscussionHero);
          return babelHelpers.possibleConstructorReturn(this, (DiscussionHero.__proto__ || Object.getPrototypeOf(DiscussionHero)).apply(this, arguments));
        }

        babelHelpers.createClass(DiscussionHero, [{
          key: 'view',
          value: function view() {
            return m(
              'header',
              { className: 'Hero DiscussionHero' },
              m(
                'div',
                { className: 'container' },
                m(
                  'ul',
                  { className: 'DiscussionHero-items' },
                  listItems(this.items().toArray())
                )
              )
            );
          }
        }, {
          key: 'items',
          value: function items() {
            var items = new ItemList();
            var discussion = this.props.discussion;
            var badges = discussion.badges().toArray();

            if (badges.length) {
              items.add('badges', m(
                'ul',
                { className: 'DiscussionHero-badges badges' },
                listItems(badges)
              ), 10);
            }

            items.add('title', m(
              'h2',
              { className: 'DiscussionHero-title' },
              discussion.title()
            ));

            return items;
          }
        }]);
        return DiscussionHero;
      }(Component);

      _export('default', DiscussionHero);
    }
  };
});;
'use strict';

System.register('flarum/components/DiscussionList', ['flarum/Component', 'flarum/components/DiscussionListItem', 'flarum/components/Button', 'flarum/components/LoadingIndicator', 'flarum/components/Placeholder'], function (_export, _context) {
  "use strict";

  var Component, DiscussionListItem, Button, LoadingIndicator, Placeholder, DiscussionList;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumComponentsDiscussionListItem) {
      DiscussionListItem = _flarumComponentsDiscussionListItem.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumComponentsLoadingIndicator) {
      LoadingIndicator = _flarumComponentsLoadingIndicator.default;
    }, function (_flarumComponentsPlaceholder) {
      Placeholder = _flarumComponentsPlaceholder.default;
    }],
    execute: function () {
      DiscussionList = function (_Component) {
        babelHelpers.inherits(DiscussionList, _Component);

        function DiscussionList() {
          babelHelpers.classCallCheck(this, DiscussionList);
          return babelHelpers.possibleConstructorReturn(this, (DiscussionList.__proto__ || Object.getPrototypeOf(DiscussionList)).apply(this, arguments));
        }

        babelHelpers.createClass(DiscussionList, [{
          key: 'init',
          value: function init() {
            /**
             * Whether or not discussion results are loading.
             *
             * @type {Boolean}
             */
            this.loading = true;

            /**
             * Whether or not there are more results that can be loaded.
             *
             * @type {Boolean}
             */
            this.moreResults = false;

            /**
             * The discussions in the discussion list.
             *
             * @type {Discussion[]}
             */
            this.discussions = [];

            this.refresh();
          }
        }, {
          key: 'view',
          value: function view() {
            var params = this.props.params;
            var loading = void 0;

            if (this.loading) {
              loading = LoadingIndicator.component();
            } else if (this.moreResults) {
              loading = Button.component({
                children: app.translator.trans('core.forum.discussion_list.load_more_button'),
                className: 'Button',
                onclick: this.loadMore.bind(this)
              });
            }

            if (this.discussions.length === 0 && !this.loading) {
              var text = app.translator.trans('core.forum.discussion_list.empty_text');
              return m(
                'div',
                { className: 'DiscussionList' },
                Placeholder.component({ text: text })
              );
            }

            return m(
              'div',
              { className: 'DiscussionList' },
              m(
                'ul',
                { className: 'DiscussionList-discussions' },
                this.discussions.map(function (discussion) {
                  return m(
                    'li',
                    { key: discussion.id(), 'data-id': discussion.id() },
                    DiscussionListItem.component({ discussion: discussion, params: params })
                  );
                })
              ),
              m(
                'div',
                { className: 'DiscussionList-loadMore' },
                loading
              )
            );
          }
        }, {
          key: 'requestParams',
          value: function requestParams() {
            var params = { include: ['startUser', 'lastUser'], filter: {} };

            params.sort = this.sortMap()[this.props.params.sort];

            if (this.props.params.q) {
              params.filter.q = this.props.params.q;

              params.include.push('relevantPosts', 'relevantPosts.discussion', 'relevantPosts.user');
            }

            return params;
          }
        }, {
          key: 'sortMap',
          value: function sortMap() {
            var map = {};

            if (this.props.params.q) {
              map.relevance = '';
            }
            map.latest = '-lastTime';
            map.top = '-commentsCount';
            map.newest = '-startTime';
            map.oldest = 'startTime';

            return map;
          }
        }, {
          key: 'refresh',
          value: function refresh() {
            var _this2 = this;

            var clear = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;

            if (clear) {
              this.loading = true;
              this.discussions = [];
            }

            return this.loadResults().then(function (results) {
              _this2.discussions = [];
              _this2.parseResults(results);
            }, function () {
              _this2.loading = false;
              m.redraw();
            });
          }
        }, {
          key: 'loadResults',
          value: function loadResults(offset) {
            var preloadedDiscussions = app.preloadedDocument();

            if (preloadedDiscussions) {
              return m.deferred().resolve(preloadedDiscussions).promise;
            }

            var params = this.requestParams();
            params.page = { offset: offset };
            params.include = params.include.join(',');

            return app.store.find('discussions', params);
          }
        }, {
          key: 'loadMore',
          value: function loadMore() {
            this.loading = true;

            this.loadResults(this.discussions.length).then(this.parseResults.bind(this));
          }
        }, {
          key: 'parseResults',
          value: function parseResults(results) {
            [].push.apply(this.discussions, results);

            this.loading = false;
            this.moreResults = !!results.payload.links.next;

            m.lazyRedraw();

            return results;
          }
        }, {
          key: 'removeDiscussion',
          value: function removeDiscussion(discussion) {
            var index = this.discussions.indexOf(discussion);

            if (index !== -1) {
              this.discussions.splice(index, 1);
            }
          }
        }, {
          key: 'addDiscussion',
          value: function addDiscussion(discussion) {
            this.discussions.unshift(discussion);
          }
        }]);
        return DiscussionList;
      }(Component);

      _export('default', DiscussionList);
    }
  };
});;
'use strict';

System.register('flarum/components/DiscussionListItem', ['flarum/Component', 'flarum/helpers/avatar', 'flarum/helpers/listItems', 'flarum/helpers/highlight', 'flarum/helpers/icon', 'flarum/utils/humanTime', 'flarum/utils/ItemList', 'flarum/utils/abbreviateNumber', 'flarum/components/Dropdown', 'flarum/components/TerminalPost', 'flarum/components/PostPreview', 'flarum/utils/SubtreeRetainer', 'flarum/utils/DiscussionControls', 'flarum/utils/slidable', 'flarum/utils/extractText', 'flarum/utils/classList'], function (_export, _context) {
  "use strict";

  var Component, avatar, listItems, highlight, icon, humanTime, ItemList, abbreviateNumber, Dropdown, TerminalPost, PostPreview, SubtreeRetainer, DiscussionControls, slidable, extractText, classList, DiscussionListItem;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumHelpersAvatar) {
      avatar = _flarumHelpersAvatar.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }, function (_flarumHelpersHighlight) {
      highlight = _flarumHelpersHighlight.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }, function (_flarumUtilsHumanTime) {
      humanTime = _flarumUtilsHumanTime.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumUtilsAbbreviateNumber) {
      abbreviateNumber = _flarumUtilsAbbreviateNumber.default;
    }, function (_flarumComponentsDropdown) {
      Dropdown = _flarumComponentsDropdown.default;
    }, function (_flarumComponentsTerminalPost) {
      TerminalPost = _flarumComponentsTerminalPost.default;
    }, function (_flarumComponentsPostPreview) {
      PostPreview = _flarumComponentsPostPreview.default;
    }, function (_flarumUtilsSubtreeRetainer) {
      SubtreeRetainer = _flarumUtilsSubtreeRetainer.default;
    }, function (_flarumUtilsDiscussionControls) {
      DiscussionControls = _flarumUtilsDiscussionControls.default;
    }, function (_flarumUtilsSlidable) {
      slidable = _flarumUtilsSlidable.default;
    }, function (_flarumUtilsExtractText) {
      extractText = _flarumUtilsExtractText.default;
    }, function (_flarumUtilsClassList) {
      classList = _flarumUtilsClassList.default;
    }],
    execute: function () {
      DiscussionListItem = function (_Component) {
        babelHelpers.inherits(DiscussionListItem, _Component);

        function DiscussionListItem() {
          babelHelpers.classCallCheck(this, DiscussionListItem);
          return babelHelpers.possibleConstructorReturn(this, (DiscussionListItem.__proto__ || Object.getPrototypeOf(DiscussionListItem)).apply(this, arguments));
        }

        babelHelpers.createClass(DiscussionListItem, [{
          key: 'init',
          value: function init() {
            var _this2 = this;

            /**
             * Set up a subtree retainer so that the discussion will not be redrawn
             * unless new data comes in.
             *
             * @type {SubtreeRetainer}
             */
            this.subtree = new SubtreeRetainer(function () {
              return _this2.props.discussion.freshness;
            }, function () {
              var time = app.session.user && app.session.user.readTime();
              return time && time.getTime();
            }, function () {
              return _this2.active();
            });
          }
        }, {
          key: 'attrs',
          value: function attrs() {
            return {
              className: classList(['DiscussionListItem', this.active() ? 'active' : '', this.props.discussion.isHidden() ? 'DiscussionListItem--hidden' : ''])
            };
          }
        }, {
          key: 'view',
          value: function view() {
            var _this3 = this;

            var retain = this.subtree.retain();

            if (retain) return retain;

            var discussion = this.props.discussion;
            var startUser = discussion.startUser();
            var isUnread = discussion.isUnread();
            var isRead = discussion.isRead();
            var showUnread = !this.showRepliesCount() && isUnread;
            var jumpTo = Math.min(discussion.lastPostNumber(), (discussion.readNumber() || 0) + 1);
            var relevantPosts = this.props.params.q ? discussion.relevantPosts() : [];
            var controls = DiscussionControls.controls(discussion, this).toArray();
            var attrs = this.attrs();

            return m(
              'div',
              attrs,
              controls.length ? Dropdown.component({
                icon: 'ellipsis-v',
                children: controls,
                className: 'DiscussionListItem-controls',
                buttonClassName: 'Button Button--icon Button--flat Slidable-underneath Slidable-underneath--right'
              }) : '',
              m(
                'a',
                { className: 'Slidable-underneath Slidable-underneath--left Slidable-underneath--elastic' + (isUnread ? '' : ' disabled'),
                  onclick: this.markAsRead.bind(this) },
                icon('check')
              ),
              m(
                'div',
                { className: 'DiscussionListItem-content Slidable-content' + (isUnread ? ' unread' : '') + (isRead ? ' read' : '') },
                m(
                  'a',
                  { href: startUser ? app.route.user(startUser) : '#',
                    className: 'DiscussionListItem-author',
                    title: extractText(app.translator.trans('core.forum.discussion_list.started_text', { user: startUser, ago: humanTime(discussion.startTime()) })),
                    config: function config(element) {
                      $(element).tooltip({ placement: 'right' });
                      m.route.apply(this, arguments);
                    } },
                  avatar(startUser, { title: '' })
                ),
                m(
                  'ul',
                  { className: 'DiscussionListItem-badges badges' },
                  listItems(discussion.badges().toArray())
                ),
                m(
                  'a',
                  { href: app.route.discussion(discussion, jumpTo),
                    config: m.route,
                    className: 'DiscussionListItem-main' },
                  m(
                    'h3',
                    { className: 'DiscussionListItem-title' },
                    highlight(discussion.title(), this.props.params.q)
                  ),
                  m(
                    'ul',
                    { className: 'DiscussionListItem-info' },
                    listItems(this.infoItems().toArray())
                  )
                ),
                m(
                  'span',
                  { className: 'DiscussionListItem-count',
                    onclick: this.markAsRead.bind(this),
                    title: showUnread ? app.translator.trans('core.forum.discussion_list.mark_as_read_tooltip') : '' },
                  abbreviateNumber(discussion[showUnread ? 'unreadCount' : 'repliesCount']())
                ),
                relevantPosts && relevantPosts.length ? m(
                  'div',
                  { className: 'DiscussionListItem-relevantPosts' },
                  relevantPosts.map(function (post) {
                    return PostPreview.component({ post: post, highlight: _this3.props.params.q });
                  })
                ) : ''
              )
            );
          }
        }, {
          key: 'config',
          value: function config(isInitialized) {
            if (isInitialized) return;

            // If we're on a touch device, set up the discussion row to be slidable.
            // This allows the user to drag the row to either side of the screen to
            // reveal controls.
            if ('ontouchstart' in window) {
              var slidableInstance = slidable(this.$().addClass('Slidable'));

              this.$('.DiscussionListItem-controls').on('hidden.bs.dropdown', function () {
                return slidableInstance.reset();
              });
            }
          }
        }, {
          key: 'active',
          value: function active() {
            var idParam = m.route.param('id');

            return idParam && idParam.split('-')[0] === this.props.discussion.id();
          }
        }, {
          key: 'showStartPost',
          value: function showStartPost() {
            return ['newest', 'oldest'].indexOf(this.props.params.sort) !== -1;
          }
        }, {
          key: 'showRepliesCount',
          value: function showRepliesCount() {
            return this.props.params.sort === 'replies';
          }
        }, {
          key: 'markAsRead',
          value: function markAsRead() {
            var discussion = this.props.discussion;

            if (discussion.isUnread()) {
              discussion.save({ readNumber: discussion.lastPostNumber() });
              m.redraw();
            }
          }
        }, {
          key: 'infoItems',
          value: function infoItems() {
            var items = new ItemList();

            items.add('terminalPost', TerminalPost.component({
              discussion: this.props.discussion,
              lastPost: !this.showStartPost()
            }));

            return items;
          }
        }]);
        return DiscussionListItem;
      }(Component);

      _export('default', DiscussionListItem);
    }
  };
});;
'use strict';

System.register('flarum/components/DiscussionPage', ['flarum/components/Page', 'flarum/utils/ItemList', 'flarum/components/DiscussionHero', 'flarum/components/PostStream', 'flarum/components/PostStreamScrubber', 'flarum/components/LoadingIndicator', 'flarum/components/SplitDropdown', 'flarum/helpers/listItems', 'flarum/utils/DiscussionControls'], function (_export, _context) {
  "use strict";

  var Page, ItemList, DiscussionHero, PostStream, PostStreamScrubber, LoadingIndicator, SplitDropdown, listItems, DiscussionControls, DiscussionPage;
  return {
    setters: [function (_flarumComponentsPage) {
      Page = _flarumComponentsPage.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumComponentsDiscussionHero) {
      DiscussionHero = _flarumComponentsDiscussionHero.default;
    }, function (_flarumComponentsPostStream) {
      PostStream = _flarumComponentsPostStream.default;
    }, function (_flarumComponentsPostStreamScrubber) {
      PostStreamScrubber = _flarumComponentsPostStreamScrubber.default;
    }, function (_flarumComponentsLoadingIndicator) {
      LoadingIndicator = _flarumComponentsLoadingIndicator.default;
    }, function (_flarumComponentsSplitDropdown) {
      SplitDropdown = _flarumComponentsSplitDropdown.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }, function (_flarumUtilsDiscussionControls) {
      DiscussionControls = _flarumUtilsDiscussionControls.default;
    }],
    execute: function () {
      DiscussionPage = function (_Page) {
        babelHelpers.inherits(DiscussionPage, _Page);

        function DiscussionPage() {
          babelHelpers.classCallCheck(this, DiscussionPage);
          return babelHelpers.possibleConstructorReturn(this, (DiscussionPage.__proto__ || Object.getPrototypeOf(DiscussionPage)).apply(this, arguments));
        }

        babelHelpers.createClass(DiscussionPage, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(DiscussionPage.prototype.__proto__ || Object.getPrototypeOf(DiscussionPage.prototype), 'init', this).call(this);

            /**
             * The discussion that is being viewed.
             *
             * @type {Discussion}
             */
            this.discussion = null;

            /**
             * The number of the first post that is currently visible in the viewport.
             *
             * @type {Integer}
             */
            this.near = null;

            this.refresh();

            // If the discussion list has been loaded, then we'll enable the pane (and
            // hide it by default). Also, if we've just come from another discussion
            // page, then we don't want Mithril to redraw the whole page – if it did,
            // then the pane would which would be slow and would cause problems with
            // event handlers.
            if (app.cache.discussionList) {
              app.pane.enable();
              app.pane.hide();

              if (app.previous instanceof DiscussionPage) {
                m.redraw.strategy('diff');
              }
            }

            app.history.push('discussion');

            this.bodyClass = 'App--discussion';
          }
        }, {
          key: 'onunload',
          value: function onunload(e) {
            // If we have routed to the same discussion as we were viewing previously,
            // cancel the unloading of this controller and instead prompt the post
            // stream to jump to the new 'near' param.
            if (this.discussion) {
              var idParam = m.route.param('id');

              if (idParam && idParam.split('-')[0] === this.discussion.id()) {
                e.preventDefault();

                var near = m.route.param('near') || '1';

                if (near !== String(this.near)) {
                  this.stream.goToNumber(near);
                }

                this.near = null;
                return;
              }
            }

            // If we are indeed navigating away from this discussion, then disable the
            // discussion list pane. Also, if we're composing a reply to this
            // discussion, minimize the composer – unless it's empty, in which case
            // we'll just close it.
            app.pane.disable();

            if (app.composingReplyTo(this.discussion) && !app.composer.component.content()) {
              app.composer.hide();
            } else {
              app.composer.minimize();
            }
          }
        }, {
          key: 'view',
          value: function view() {
            var discussion = this.discussion;

            return m(
              'div',
              { className: 'DiscussionPage' },
              app.cache.discussionList ? m(
                'div',
                { className: 'DiscussionPage-list', config: this.configPane.bind(this) },
                !$('.App-navigation').is(':visible') ? app.cache.discussionList.render() : ''
              ) : '',
              m(
                'div',
                { className: 'DiscussionPage-discussion' },
                discussion ? [DiscussionHero.component({ discussion: discussion }), m(
                  'div',
                  { className: 'container' },
                  m(
                    'nav',
                    { className: 'DiscussionPage-nav' },
                    m(
                      'ul',
                      null,
                      listItems(this.sidebarItems().toArray())
                    )
                  ),
                  m(
                    'div',
                    { className: 'DiscussionPage-stream' },
                    this.stream.render()
                  )
                )] : LoadingIndicator.component({ className: 'LoadingIndicator--block' })
              )
            );
          }
        }, {
          key: 'refresh',
          value: function refresh() {
            this.near = m.route.param('near') || 0;
            this.discussion = null;

            var preloadedDiscussion = app.preloadedDocument();
            if (preloadedDiscussion) {
              // We must wrap this in a setTimeout because if we are mounting this
              // component for the first time on page load, then any calls to m.redraw
              // will be ineffective and thus any configs (scroll code) will be run
              // before stuff is drawn to the page.
              setTimeout(this.show.bind(this, preloadedDiscussion), 0);
            } else {
              var params = this.requestParams();

              app.store.find('discussions', m.route.param('id').split('-')[0], params).then(this.show.bind(this));
            }

            m.lazyRedraw();
          }
        }, {
          key: 'requestParams',
          value: function requestParams() {
            return {
              page: { near: this.near }
            };
          }
        }, {
          key: 'show',
          value: function show(discussion) {
            this.discussion = discussion;

            app.history.push('discussion', discussion.title());
            app.setTitle(discussion.title());
            app.setTitleCount(0);

            // When the API responds with a discussion, it will also include a number of
            // posts. Some of these posts are included because they are on the first
            // page of posts we want to display (determined by the `near` parameter) –
            // others may be included because due to other relationships introduced by
            // extensions. We need to distinguish the two so we don't end up displaying
            // the wrong posts. We do so by filtering out the posts that don't have
            // the 'discussion' relationship linked, then sorting and splicing.
            var includedPosts = [];
            if (discussion.payload && discussion.payload.included) {
              includedPosts = discussion.payload.included.filter(function (record) {
                return record.type === 'posts' && record.relationships && record.relationships.discussion;
              }).map(function (record) {
                return app.store.getById('posts', record.id);
              }).sort(function (a, b) {
                return a.id() - b.id();
              }).slice(0, 20);
            }

            // Set up the post stream for this discussion, along with the first page of
            // posts we want to display. Tell the stream to scroll down and highlight
            // the specific post that was routed to.
            this.stream = new PostStream({ discussion: discussion, includedPosts: includedPosts });
            this.stream.on('positionChanged', this.positionChanged.bind(this));
            this.stream.goToNumber(m.route.param('near') || includedPosts[0] && includedPosts[0].number(), true);
          }
        }, {
          key: 'configPane',
          value: function configPane(element, isInitialized, context) {
            if (isInitialized) return;

            context.retain = true;

            var $list = $(element);

            // When the mouse enters and leaves the discussions pane, we want to show
            // and hide the pane respectively. We also create a 10px 'hot edge' on the
            // left of the screen to activate the pane.
            var pane = app.pane;
            $list.hover(pane.show.bind(pane), pane.onmouseleave.bind(pane));

            var hotEdge = function hotEdge(e) {
              if (e.pageX < 10) pane.show();
            };
            $(document).on('mousemove', hotEdge);
            context.onunload = function () {
              return $(document).off('mousemove', hotEdge);
            };

            // If the discussion we are viewing is listed in the discussion list, then
            // we will make sure it is visible in the viewport – if it is not we will
            // scroll the list down to it.
            var $discussion = $list.find('.DiscussionListItem.active');
            if ($discussion.length) {
              var listTop = $list.offset().top;
              var listBottom = listTop + $list.outerHeight();
              var discussionTop = $discussion.offset().top;
              var discussionBottom = discussionTop + $discussion.outerHeight();

              if (discussionTop < listTop || discussionBottom > listBottom) {
                $list.scrollTop($list.scrollTop() - listTop + discussionTop);
              }
            }
          }
        }, {
          key: 'sidebarItems',
          value: function sidebarItems() {
            var items = new ItemList();

            items.add('controls', SplitDropdown.component({
              children: DiscussionControls.controls(this.discussion, this).toArray(),
              icon: 'ellipsis-v',
              className: 'App-primaryControl',
              buttonClassName: 'Button--primary'
            }));

            items.add('scrubber', PostStreamScrubber.component({
              stream: this.stream,
              className: 'App-titleControl'
            }), -100);

            return items;
          }
        }, {
          key: 'positionChanged',
          value: function positionChanged(startNumber, endNumber) {
            var discussion = this.discussion;

            // Construct a URL to this discussion with the updated position, then
            // replace it into the window's history and our own history stack.
            var url = app.route.discussion(discussion, this.near = startNumber);

            m.route(url, true);
            window.history.replaceState(null, document.title, url);

            app.history.push('discussion', discussion.title());

            // If the user hasn't read past here before, then we'll update their read
            // state and redraw.
            if (app.session.user && endNumber > (discussion.readNumber() || 0)) {
              discussion.save({ readNumber: endNumber });
              m.redraw();
            }
          }
        }]);
        return DiscussionPage;
      }(Page);

      _export('default', DiscussionPage);
    }
  };
});;
'use strict';

System.register('flarum/components/DiscussionRenamedNotification', ['flarum/components/Notification'], function (_export, _context) {
  "use strict";

  var Notification, DiscussionRenamedNotification;
  return {
    setters: [function (_flarumComponentsNotification) {
      Notification = _flarumComponentsNotification.default;
    }],
    execute: function () {
      DiscussionRenamedNotification = function (_Notification) {
        babelHelpers.inherits(DiscussionRenamedNotification, _Notification);

        function DiscussionRenamedNotification() {
          babelHelpers.classCallCheck(this, DiscussionRenamedNotification);
          return babelHelpers.possibleConstructorReturn(this, (DiscussionRenamedNotification.__proto__ || Object.getPrototypeOf(DiscussionRenamedNotification)).apply(this, arguments));
        }

        babelHelpers.createClass(DiscussionRenamedNotification, [{
          key: 'icon',
          value: function icon() {
            return 'pencil';
          }
        }, {
          key: 'href',
          value: function href() {
            var notification = this.props.notification;

            return app.route.discussion(notification.subject(), notification.content().postNumber);
          }
        }, {
          key: 'content',
          value: function content() {
            return app.translator.trans('core.forum.notifications.discussion_renamed_text', { user: this.props.notification.sender() });
          }
        }]);
        return DiscussionRenamedNotification;
      }(Notification);

      _export('default', DiscussionRenamedNotification);
    }
  };
});;
'use strict';

System.register('flarum/components/DiscussionRenamedPost', ['flarum/components/EventPost', 'flarum/utils/extractText'], function (_export, _context) {
  "use strict";

  var EventPost, extractText, DiscussionRenamedPost;
  return {
    setters: [function (_flarumComponentsEventPost) {
      EventPost = _flarumComponentsEventPost.default;
    }, function (_flarumUtilsExtractText) {
      extractText = _flarumUtilsExtractText.default;
    }],
    execute: function () {
      DiscussionRenamedPost = function (_EventPost) {
        babelHelpers.inherits(DiscussionRenamedPost, _EventPost);

        function DiscussionRenamedPost() {
          babelHelpers.classCallCheck(this, DiscussionRenamedPost);
          return babelHelpers.possibleConstructorReturn(this, (DiscussionRenamedPost.__proto__ || Object.getPrototypeOf(DiscussionRenamedPost)).apply(this, arguments));
        }

        babelHelpers.createClass(DiscussionRenamedPost, [{
          key: 'icon',
          value: function icon() {
            return 'pencil';
          }
        }, {
          key: 'description',
          value: function description(data) {
            var renamed = app.translator.trans('core.forum.post_stream.discussion_renamed_text', data);
            var oldName = app.translator.trans('core.forum.post_stream.discussion_renamed_old_tooltip', data);

            return m(
              'span',
              { title: extractText(oldName) },
              renamed
            );
          }
        }, {
          key: 'descriptionData',
          value: function descriptionData() {
            var post = this.props.post;
            var oldTitle = post.content()[0];
            var newTitle = post.content()[1];

            return {
              'old': oldTitle,
              'new': m(
                'strong',
                { className: 'DiscussionRenamedPost-new' },
                newTitle
              )
            };
          }
        }]);
        return DiscussionRenamedPost;
      }(EventPost);

      _export('default', DiscussionRenamedPost);
    }
  };
});;
'use strict';

System.register('flarum/components/DiscussionsSearchSource', ['flarum/helpers/highlight', 'flarum/components/LinkButton'], function (_export, _context) {
  "use strict";

  var highlight, LinkButton, DiscussionsSearchSource;
  return {
    setters: [function (_flarumHelpersHighlight) {
      highlight = _flarumHelpersHighlight.default;
    }, function (_flarumComponentsLinkButton) {
      LinkButton = _flarumComponentsLinkButton.default;
    }],
    execute: function () {
      DiscussionsSearchSource = function () {
        function DiscussionsSearchSource() {
          babelHelpers.classCallCheck(this, DiscussionsSearchSource);

          this.results = {};
        }

        babelHelpers.createClass(DiscussionsSearchSource, [{
          key: 'search',
          value: function search(query) {
            var _this = this;

            query = query.toLowerCase();

            this.results[query] = [];

            var params = {
              filter: { q: query },
              page: { limit: 3 },
              include: 'relevantPosts,relevantPosts.discussion,relevantPosts.user'
            };

            return app.store.find('discussions', params).then(function (results) {
              return _this.results[query] = results;
            });
          }
        }, {
          key: 'view',
          value: function view(query) {
            query = query.toLowerCase();

            var results = this.results[query] || [];

            return [m(
              'li',
              { className: 'Dropdown-header' },
              app.translator.trans('core.forum.search.discussions_heading')
            ), m(
              'li',
              null,
              LinkButton.component({
                icon: 'search',
                children: app.translator.trans('core.forum.search.all_discussions_button', { query: query }),
                href: app.route('index', { q: query })
              })
            ), results.map(function (discussion) {
              var relevantPosts = discussion.relevantPosts();
              var post = relevantPosts && relevantPosts[0];

              return m(
                'li',
                { className: 'DiscussionSearchResult', 'data-index': 'discussions' + discussion.id() },
                m(
                  'a',
                  { href: app.route.discussion(discussion, post && post.number()), config: m.route },
                  m(
                    'div',
                    { className: 'DiscussionSearchResult-title' },
                    highlight(discussion.title(), query)
                  ),
                  post ? m(
                    'div',
                    { className: 'DiscussionSearchResult-excerpt' },
                    highlight(post.contentPlain(), query, 100)
                  ) : ''
                )
              );
            })];
          }
        }]);
        return DiscussionsSearchSource;
      }();

      _export('default', DiscussionsSearchSource);
    }
  };
});;
'use strict';

System.register('flarum/components/DiscussionsUserPage', ['flarum/components/UserPage', 'flarum/components/DiscussionList'], function (_export, _context) {
  "use strict";

  var UserPage, DiscussionList, DiscussionsUserPage;
  return {
    setters: [function (_flarumComponentsUserPage) {
      UserPage = _flarumComponentsUserPage.default;
    }, function (_flarumComponentsDiscussionList) {
      DiscussionList = _flarumComponentsDiscussionList.default;
    }],
    execute: function () {
      DiscussionsUserPage = function (_UserPage) {
        babelHelpers.inherits(DiscussionsUserPage, _UserPage);

        function DiscussionsUserPage() {
          babelHelpers.classCallCheck(this, DiscussionsUserPage);
          return babelHelpers.possibleConstructorReturn(this, (DiscussionsUserPage.__proto__ || Object.getPrototypeOf(DiscussionsUserPage)).apply(this, arguments));
        }

        babelHelpers.createClass(DiscussionsUserPage, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(DiscussionsUserPage.prototype.__proto__ || Object.getPrototypeOf(DiscussionsUserPage.prototype), 'init', this).call(this);

            this.loadUser(m.route.param('username'));
          }
        }, {
          key: 'content',
          value: function content() {
            return m(
              'div',
              { className: 'DiscussionsUserPage' },
              DiscussionList.component({
                params: {
                  q: 'author:' + this.user.username()
                }
              })
            );
          }
        }]);
        return DiscussionsUserPage;
      }(UserPage);

      _export('default', DiscussionsUserPage);
    }
  };
});;
'use strict';

System.register('flarum/components/Dropdown', ['flarum/Component', 'flarum/helpers/icon', 'flarum/helpers/listItems'], function (_export, _context) {
  "use strict";

  var Component, icon, listItems, Dropdown;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }],
    execute: function () {
      Dropdown = function (_Component) {
        babelHelpers.inherits(Dropdown, _Component);

        function Dropdown() {
          babelHelpers.classCallCheck(this, Dropdown);
          return babelHelpers.possibleConstructorReturn(this, (Dropdown.__proto__ || Object.getPrototypeOf(Dropdown)).apply(this, arguments));
        }

        babelHelpers.createClass(Dropdown, [{
          key: 'init',
          value: function init() {
            this.showing = false;
          }
        }, {
          key: 'view',
          value: function view() {
            var items = this.props.children ? listItems(this.props.children) : [];

            return m(
              'div',
              { className: 'ButtonGroup Dropdown dropdown ' + this.props.className + ' itemCount' + items.length + (this.showing ? ' open' : '') },
              this.getButton(),
              this.getMenu(items)
            );
          }
        }, {
          key: 'config',
          value: function config(isInitialized) {
            var _this2 = this;

            if (isInitialized) return;

            // When opening the dropdown menu, work out if the menu goes beyond the
            // bottom of the viewport. If it does, we will apply class to make it show
            // above the toggle button instead of below it.
            this.$().on('shown.bs.dropdown', function () {
              _this2.showing = true;

              if (_this2.props.onshow) {
                _this2.props.onshow();
              }

              m.redraw();

              var $menu = _this2.$('.Dropdown-menu');
              var isRight = $menu.hasClass('Dropdown-menu--right');

              $menu.removeClass('Dropdown-menu--top Dropdown-menu--right');

              $menu.toggleClass('Dropdown-menu--top', $menu.offset().top + $menu.height() > $(window).scrollTop() + $(window).height());

              $menu.toggleClass('Dropdown-menu--right', isRight || $menu.offset().left + $menu.width() > $(window).scrollLeft() + $(window).width());
            });

            this.$().on('hidden.bs.dropdown', function () {
              _this2.showing = false;

              if (_this2.props.onhide) {
                _this2.props.onhide();
              }

              m.redraw();
            });
          }
        }, {
          key: 'getButton',
          value: function getButton() {
            return m(
              'button',
              {
                className: 'Dropdown-toggle ' + this.props.buttonClassName,
                'data-toggle': 'dropdown',
                onclick: this.props.onclick },
              this.getButtonContent()
            );
          }
        }, {
          key: 'getButtonContent',
          value: function getButtonContent() {
            return [this.props.icon ? icon(this.props.icon, { className: 'Button-icon' }) : '', m(
              'span',
              { className: 'Button-label' },
              this.props.label
            ), this.props.caretIcon ? icon(this.props.caretIcon, { className: 'Button-caret' }) : ''];
          }
        }, {
          key: 'getMenu',
          value: function getMenu(items) {
            return m(
              'ul',
              { className: 'Dropdown-menu dropdown-menu ' + this.props.menuClassName },
              items
            );
          }
        }], [{
          key: 'initProps',
          value: function initProps(props) {
            babelHelpers.get(Dropdown.__proto__ || Object.getPrototypeOf(Dropdown), 'initProps', this).call(this, props);

            props.className = props.className || '';
            props.buttonClassName = props.buttonClassName || '';
            props.menuClassName = props.menuClassName || '';
            props.label = props.label || '';
            props.caretIcon = typeof props.caretIcon !== 'undefined' ? props.caretIcon : 'caret-down';
          }
        }]);
        return Dropdown;
      }(Component);

      _export('default', Dropdown);
    }
  };
});;
'use strict';

System.register('flarum/components/EditPostComposer', ['flarum/components/ComposerBody', 'flarum/helpers/icon'], function (_export, _context) {
  "use strict";

  var ComposerBody, icon, EditPostComposer;


  function minimizeComposerIfFullScreen(e) {
    if (app.composer.isFullScreen()) {
      app.composer.minimize();
      e.stopPropagation();
    }
  }

  /**
   * The `EditPostComposer` component displays the composer content for editing a
   * post. It sets the initial content to the content of the post that is being
   * edited, and adds a header control to indicate which post is being edited.
   *
   * ### Props
   *
   * - All of the props for ComposerBody
   * - `post`
   */
  return {
    setters: [function (_flarumComponentsComposerBody) {
      ComposerBody = _flarumComponentsComposerBody.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }],
    execute: function () {
      EditPostComposer = function (_ComposerBody) {
        babelHelpers.inherits(EditPostComposer, _ComposerBody);

        function EditPostComposer() {
          babelHelpers.classCallCheck(this, EditPostComposer);
          return babelHelpers.possibleConstructorReturn(this, (EditPostComposer.__proto__ || Object.getPrototypeOf(EditPostComposer)).apply(this, arguments));
        }

        babelHelpers.createClass(EditPostComposer, [{
          key: 'init',
          value: function init() {
            var _this2 = this;

            babelHelpers.get(EditPostComposer.prototype.__proto__ || Object.getPrototypeOf(EditPostComposer.prototype), 'init', this).call(this);

            this.editor.props.preview = function (e) {
              minimizeComposerIfFullScreen(e);

              m.route(app.route.post(_this2.props.post));
            };
          }
        }, {
          key: 'headerItems',
          value: function headerItems() {
            var items = babelHelpers.get(EditPostComposer.prototype.__proto__ || Object.getPrototypeOf(EditPostComposer.prototype), 'headerItems', this).call(this);
            var post = this.props.post;

            var routeAndMinimize = function routeAndMinimize(element, isInitialized) {
              if (isInitialized) return;
              $(element).on('click', minimizeComposerIfFullScreen);
              m.route.apply(this, arguments);
            };

            items.add('title', m(
              'h3',
              null,
              icon('pencil'),
              ' ',
              ' ',
              m(
                'a',
                { href: app.route.discussion(post.discussion(), post.number()), config: routeAndMinimize },
                app.translator.trans('core.forum.composer_edit.post_link', { number: post.number(), discussion: post.discussion().title() })
              )
            ));

            return items;
          }
        }, {
          key: 'data',
          value: function data() {
            return {
              content: this.content()
            };
          }
        }, {
          key: 'onsubmit',
          value: function onsubmit() {
            this.loading = true;

            var data = this.data();

            this.props.post.save(data).then(function () {
              return app.composer.hide();
            }, this.loaded.bind(this));
          }
        }], [{
          key: 'initProps',
          value: function initProps(props) {
            babelHelpers.get(EditPostComposer.__proto__ || Object.getPrototypeOf(EditPostComposer), 'initProps', this).call(this, props);

            props.submitLabel = props.submitLabel || app.translator.trans('core.forum.composer_edit.submit_button');
            props.confirmExit = props.confirmExit || app.translator.trans('core.forum.composer_edit.discard_confirmation');
            props.originalContent = props.originalContent || props.post.content();
            props.user = props.user || props.post.user();

            props.post.editedContent = props.originalContent;
          }
        }]);
        return EditPostComposer;
      }(ComposerBody);

      _export('default', EditPostComposer);
    }
  };
});;
'use strict';

System.register('flarum/components/EditUserModal', ['flarum/components/Modal', 'flarum/components/Button', 'flarum/components/GroupBadge', 'flarum/models/Group', 'flarum/utils/extractText'], function (_export, _context) {
  "use strict";

  var Modal, Button, GroupBadge, Group, extractText, EditUserModal;
  return {
    setters: [function (_flarumComponentsModal) {
      Modal = _flarumComponentsModal.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumComponentsGroupBadge) {
      GroupBadge = _flarumComponentsGroupBadge.default;
    }, function (_flarumModelsGroup) {
      Group = _flarumModelsGroup.default;
    }, function (_flarumUtilsExtractText) {
      extractText = _flarumUtilsExtractText.default;
    }],
    execute: function () {
      EditUserModal = function (_Modal) {
        babelHelpers.inherits(EditUserModal, _Modal);

        function EditUserModal() {
          babelHelpers.classCallCheck(this, EditUserModal);
          return babelHelpers.possibleConstructorReturn(this, (EditUserModal.__proto__ || Object.getPrototypeOf(EditUserModal)).apply(this, arguments));
        }

        babelHelpers.createClass(EditUserModal, [{
          key: 'init',
          value: function init() {
            var _this2 = this;

            babelHelpers.get(EditUserModal.prototype.__proto__ || Object.getPrototypeOf(EditUserModal.prototype), 'init', this).call(this);

            var user = this.props.user;

            this.username = m.prop(user.username() || '');
            this.email = m.prop(user.email() || '');
            this.isActivated = m.prop(user.isActivated() || false);
            this.setPassword = m.prop(false);
            this.password = m.prop(user.password() || '');
            this.groups = {};

            app.store.all('groups').filter(function (group) {
              return [Group.GUEST_ID, Group.MEMBER_ID].indexOf(group.id()) === -1;
            }).forEach(function (group) {
              return _this2.groups[group.id()] = m.prop(user.groups().indexOf(group) !== -1);
            });
          }
        }, {
          key: 'className',
          value: function className() {
            return 'EditUserModal Modal--small';
          }
        }, {
          key: 'title',
          value: function title() {
            return app.translator.trans('core.forum.edit_user.title');
          }
        }, {
          key: 'content',
          value: function content() {
            var _this3 = this;

            return m(
              'div',
              { className: 'Modal-body' },
              m(
                'div',
                { className: 'Form' },
                m(
                  'div',
                  { className: 'Form-group' },
                  m(
                    'label',
                    null,
                    app.translator.trans('core.forum.edit_user.username_heading')
                  ),
                  m('input', { className: 'FormControl', placeholder: extractText(app.translator.trans('core.forum.edit_user.username_label')),
                    bidi: this.username })
                ),
                app.session.user !== this.props.user ? [m(
                  'div',
                  { className: 'Form-group' },
                  m(
                    'label',
                    null,
                    app.translator.trans('core.forum.edit_user.email_heading')
                  ),
                  m(
                    'div',
                    null,
                    m('input', { className: 'FormControl', placeholder: extractText(app.translator.trans('core.forum.edit_user.email_label')),
                      bidi: this.email })
                  ),
                  !this.isActivated() ? m(
                    'div',
                    null,
                    Button.component({
                      className: 'Button Button--block',
                      children: app.translator.trans('core.forum.edit_user.activate_button'),
                      loading: this.loading,
                      onclick: this.activate.bind(this)
                    })
                  ) : ''
                ), m(
                  'div',
                  { className: 'Form-group' },
                  m(
                    'label',
                    null,
                    app.translator.trans('core.forum.edit_user.password_heading')
                  ),
                  m(
                    'div',
                    null,
                    m(
                      'label',
                      { className: 'checkbox' },
                      m('input', { type: 'checkbox', checked: this.setPassword(), onchange: function onchange(e) {
                          _this3.setPassword(e.target.checked);
                          m.redraw(true);
                          if (e.target.checked) _this3.$('[name=password]').select();
                          m.redraw.strategy('none');
                        } }),
                      app.translator.trans('core.forum.edit_user.set_password_label')
                    ),
                    this.setPassword() ? m('input', { className: 'FormControl', type: 'password', name: 'password', placeholder: extractText(app.translator.trans('core.forum.edit_user.password_label')),
                      bidi: this.password }) : ''
                  )
                )] : '',
                m(
                  'div',
                  { className: 'Form-group EditUserModal-groups' },
                  m(
                    'label',
                    null,
                    app.translator.trans('core.forum.edit_user.groups_heading')
                  ),
                  m(
                    'div',
                    null,
                    Object.keys(this.groups).map(function (id) {
                      return app.store.getById('groups', id);
                    }).map(function (group) {
                      return m(
                        'label',
                        { className: 'checkbox' },
                        m('input', { type: 'checkbox',
                          bidi: _this3.groups[group.id()],
                          disabled: _this3.props.user.id() === '1' && group.id() === Group.ADMINISTRATOR_ID }),
                        GroupBadge.component({ group: group, label: '' }),
                        ' ',
                        group.nameSingular()
                      );
                    })
                  )
                ),
                m(
                  'div',
                  { className: 'Form-group' },
                  Button.component({
                    className: 'Button Button--primary',
                    type: 'submit',
                    loading: this.loading,
                    children: app.translator.trans('core.forum.edit_user.submit_button')
                  })
                )
              )
            );
          }
        }, {
          key: 'activate',
          value: function activate() {
            var _this4 = this;

            this.loading = true;
            var data = {
              username: this.username(),
              isActivated: true
            };
            this.props.user.save(data, { errorHandler: this.onerror.bind(this) }).then(function () {
              _this4.isActivated(true);
              _this4.loading = false;
              m.redraw();
            }).catch(function () {
              _this4.loading = false;
              m.redraw();
            });
          }
        }, {
          key: 'data',
          value: function data() {
            var _this5 = this;

            var groups = Object.keys(this.groups).filter(function (id) {
              return _this5.groups[id]();
            }).map(function (id) {
              return app.store.getById('groups', id);
            });

            var data = {
              username: this.username(),
              relationships: { groups: groups }
            };

            if (app.session.user !== this.props.user) {
              data.email = this.email();
            }

            if (this.setPassword()) {
              data.password = this.password();
            }

            return data;
          }
        }, {
          key: 'onsubmit',
          value: function onsubmit(e) {
            var _this6 = this;

            e.preventDefault();

            this.loading = true;

            this.props.user.save(this.data(), { errorHandler: this.onerror.bind(this) }).then(this.hide.bind(this)).catch(function () {
              _this6.loading = false;
              m.redraw();
            });
          }
        }]);
        return EditUserModal;
      }(Modal);

      _export('default', EditUserModal);
    }
  };
});;
'use strict';

System.register('flarum/components/EventPost', ['flarum/components/Post', 'flarum/utils/string', 'flarum/helpers/username', 'flarum/helpers/icon'], function (_export, _context) {
  "use strict";

  var Post, ucfirst, usernameHelper, icon, EventPost;
  return {
    setters: [function (_flarumComponentsPost) {
      Post = _flarumComponentsPost.default;
    }, function (_flarumUtilsString) {
      ucfirst = _flarumUtilsString.ucfirst;
    }, function (_flarumHelpersUsername) {
      usernameHelper = _flarumHelpersUsername.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }],
    execute: function () {
      EventPost = function (_Post) {
        babelHelpers.inherits(EventPost, _Post);

        function EventPost() {
          babelHelpers.classCallCheck(this, EventPost);
          return babelHelpers.possibleConstructorReturn(this, (EventPost.__proto__ || Object.getPrototypeOf(EventPost)).apply(this, arguments));
        }

        babelHelpers.createClass(EventPost, [{
          key: 'attrs',
          value: function attrs() {
            var attrs = babelHelpers.get(EventPost.prototype.__proto__ || Object.getPrototypeOf(EventPost.prototype), 'attrs', this).call(this);

            attrs.className += ' EventPost ' + ucfirst(this.props.post.contentType()) + 'Post';

            return attrs;
          }
        }, {
          key: 'content',
          value: function content() {
            var user = this.props.post.user();
            var username = usernameHelper(user);
            var data = babelHelpers.extends(this.descriptionData(), {
              user: user,
              username: user ? m(
                'a',
                { className: 'EventPost-user', href: app.route.user(user), config: m.route },
                username
              ) : username
            });

            return babelHelpers.get(EventPost.prototype.__proto__ || Object.getPrototypeOf(EventPost.prototype), 'content', this).call(this).concat([icon(this.icon(), { className: 'EventPost-icon' }), m(
              'div',
              { 'class': 'EventPost-info' },
              this.description(data)
            )]);
          }
        }, {
          key: 'icon',
          value: function icon() {
            return '';
          }
        }, {
          key: 'description',
          value: function description(data) {
            return app.translator.transChoice(this.descriptionKey(), data.count, data);
          }
        }, {
          key: 'descriptionKey',
          value: function descriptionKey() {
            return '';
          }
        }, {
          key: 'descriptionData',
          value: function descriptionData() {
            return {};
          }
        }]);
        return EventPost;
      }(Post);

      _export('default', EventPost);
    }
  };
});;
'use strict';

System.register('flarum/components/FieldSet', ['flarum/Component', 'flarum/helpers/listItems'], function (_export, _context) {
  "use strict";

  var Component, listItems, FieldSet;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }],
    execute: function () {
      FieldSet = function (_Component) {
        babelHelpers.inherits(FieldSet, _Component);

        function FieldSet() {
          babelHelpers.classCallCheck(this, FieldSet);
          return babelHelpers.possibleConstructorReturn(this, (FieldSet.__proto__ || Object.getPrototypeOf(FieldSet)).apply(this, arguments));
        }

        babelHelpers.createClass(FieldSet, [{
          key: 'view',
          value: function view() {
            return m(
              'fieldset',
              { className: this.props.className },
              m(
                'legend',
                null,
                this.props.label
              ),
              m(
                'ul',
                null,
                listItems(this.props.children)
              )
            );
          }
        }]);
        return FieldSet;
      }(Component);

      _export('default', FieldSet);
    }
  };
});;
'use strict';

System.register('flarum/components/ForgotPasswordModal', ['flarum/components/Modal', 'flarum/components/Alert', 'flarum/components/Button', 'flarum/utils/extractText'], function (_export, _context) {
  "use strict";

  var Modal, Alert, Button, extractText, ForgotPasswordModal;
  return {
    setters: [function (_flarumComponentsModal) {
      Modal = _flarumComponentsModal.default;
    }, function (_flarumComponentsAlert) {
      Alert = _flarumComponentsAlert.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumUtilsExtractText) {
      extractText = _flarumUtilsExtractText.default;
    }],
    execute: function () {
      ForgotPasswordModal = function (_Modal) {
        babelHelpers.inherits(ForgotPasswordModal, _Modal);

        function ForgotPasswordModal() {
          babelHelpers.classCallCheck(this, ForgotPasswordModal);
          return babelHelpers.possibleConstructorReturn(this, (ForgotPasswordModal.__proto__ || Object.getPrototypeOf(ForgotPasswordModal)).apply(this, arguments));
        }

        babelHelpers.createClass(ForgotPasswordModal, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(ForgotPasswordModal.prototype.__proto__ || Object.getPrototypeOf(ForgotPasswordModal.prototype), 'init', this).call(this);

            /**
             * The value of the email input.
             *
             * @type {Function}
             */
            this.email = m.prop(this.props.email || '');

            /**
             * Whether or not the password reset email was sent successfully.
             *
             * @type {Boolean}
             */
            this.success = false;
          }
        }, {
          key: 'className',
          value: function className() {
            return 'ForgotPasswordModal Modal--small';
          }
        }, {
          key: 'title',
          value: function title() {
            return app.translator.trans('core.forum.forgot_password.title');
          }
        }, {
          key: 'content',
          value: function content() {
            if (this.success) {
              return m(
                'div',
                { className: 'Modal-body' },
                m(
                  'div',
                  { className: 'Form Form--centered' },
                  m(
                    'p',
                    { className: 'helpText' },
                    app.translator.trans('core.forum.forgot_password.email_sent_message')
                  ),
                  m(
                    'div',
                    { className: 'Form-group' },
                    m(
                      Button,
                      { className: 'Button Button--primary Button--block', onclick: this.hide.bind(this) },
                      app.translator.trans('core.forum.forgot_password.dismiss_button')
                    )
                  )
                )
              );
            }

            return m(
              'div',
              { className: 'Modal-body' },
              m(
                'div',
                { className: 'Form Form--centered' },
                m(
                  'p',
                  { className: 'helpText' },
                  app.translator.trans('core.forum.forgot_password.text')
                ),
                m(
                  'div',
                  { className: 'Form-group' },
                  m('input', { className: 'FormControl', name: 'email', type: 'email', placeholder: extractText(app.translator.trans('core.forum.forgot_password.email_placeholder')),
                    value: this.email(),
                    onchange: m.withAttr('value', this.email),
                    disabled: this.loading })
                ),
                m(
                  'div',
                  { className: 'Form-group' },
                  Button.component({
                    className: 'Button Button--primary Button--block',
                    type: 'submit',
                    loading: this.loading,
                    children: app.translator.trans('core.forum.forgot_password.submit_button')
                  })
                )
              )
            );
          }
        }, {
          key: 'onsubmit',
          value: function onsubmit(e) {
            var _this2 = this;

            e.preventDefault();

            this.loading = true;

            app.request({
              method: 'POST',
              url: app.forum.attribute('apiUrl') + '/forgot',
              data: { email: this.email() },
              errorHandler: this.onerror.bind(this)
            }).then(function () {
              _this2.success = true;
              _this2.alert = null;
            }).catch(function () {}).then(this.loaded.bind(this));
          }
        }, {
          key: 'onerror',
          value: function onerror(error) {
            if (error.status === 404) {
              error.alert.props.children = app.translator.trans('core.forum.forgot_password.not_found_message');
            }

            babelHelpers.get(ForgotPasswordModal.prototype.__proto__ || Object.getPrototypeOf(ForgotPasswordModal.prototype), 'onerror', this).call(this, error);
          }
        }]);
        return ForgotPasswordModal;
      }(Modal);

      _export('default', ForgotPasswordModal);
    }
  };
});;
'use strict';

System.register('flarum/components/GroupBadge', ['flarum/components/Badge'], function (_export, _context) {
  "use strict";

  var Badge, GroupBadge;
  return {
    setters: [function (_flarumComponentsBadge) {
      Badge = _flarumComponentsBadge.default;
    }],
    execute: function () {
      GroupBadge = function (_Badge) {
        babelHelpers.inherits(GroupBadge, _Badge);

        function GroupBadge() {
          babelHelpers.classCallCheck(this, GroupBadge);
          return babelHelpers.possibleConstructorReturn(this, (GroupBadge.__proto__ || Object.getPrototypeOf(GroupBadge)).apply(this, arguments));
        }

        babelHelpers.createClass(GroupBadge, null, [{
          key: 'initProps',
          value: function initProps(props) {
            babelHelpers.get(GroupBadge.__proto__ || Object.getPrototypeOf(GroupBadge), 'initProps', this).call(this, props);

            if (props.group) {
              props.icon = props.group.icon();
              props.style = { backgroundColor: props.group.color() };
              props.label = typeof props.label === 'undefined' ? props.group.nameSingular() : props.label;
              props.type = 'group--' + props.group.id();

              delete props.group;
            }
          }
        }]);
        return GroupBadge;
      }(Badge);

      _export('default', GroupBadge);
    }
  };
});;
'use strict';

System.register('flarum/components/HeaderPrimary', ['flarum/Component', 'flarum/utils/ItemList', 'flarum/helpers/listItems'], function (_export, _context) {
  "use strict";

  var Component, ItemList, listItems, HeaderPrimary;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }],
    execute: function () {
      HeaderPrimary = function (_Component) {
        babelHelpers.inherits(HeaderPrimary, _Component);

        function HeaderPrimary() {
          babelHelpers.classCallCheck(this, HeaderPrimary);
          return babelHelpers.possibleConstructorReturn(this, (HeaderPrimary.__proto__ || Object.getPrototypeOf(HeaderPrimary)).apply(this, arguments));
        }

        babelHelpers.createClass(HeaderPrimary, [{
          key: 'view',
          value: function view() {
            return m(
              'ul',
              { className: 'Header-controls' },
              listItems(this.items().toArray())
            );
          }
        }, {
          key: 'config',
          value: function config(isInitialized, context) {
            // Since this component is 'above' the content of the page (that is, it is a
            // part of the global UI that persists between routes), we will flag the DOM
            // to be retained across route changes.
            context.retain = true;
          }
        }, {
          key: 'items',
          value: function items() {
            return new ItemList();
          }
        }]);
        return HeaderPrimary;
      }(Component);

      _export('default', HeaderPrimary);
    }
  };
});;
'use strict';

System.register('flarum/components/HeaderSecondary', ['flarum/Component', 'flarum/components/Button', 'flarum/components/LogInModal', 'flarum/components/SignUpModal', 'flarum/components/SessionDropdown', 'flarum/components/SelectDropdown', 'flarum/components/NotificationsDropdown', 'flarum/utils/ItemList', 'flarum/helpers/listItems'], function (_export, _context) {
  "use strict";

  var Component, Button, LogInModal, SignUpModal, SessionDropdown, SelectDropdown, NotificationsDropdown, ItemList, listItems, HeaderSecondary;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumComponentsLogInModal) {
      LogInModal = _flarumComponentsLogInModal.default;
    }, function (_flarumComponentsSignUpModal) {
      SignUpModal = _flarumComponentsSignUpModal.default;
    }, function (_flarumComponentsSessionDropdown) {
      SessionDropdown = _flarumComponentsSessionDropdown.default;
    }, function (_flarumComponentsSelectDropdown) {
      SelectDropdown = _flarumComponentsSelectDropdown.default;
    }, function (_flarumComponentsNotificationsDropdown) {
      NotificationsDropdown = _flarumComponentsNotificationsDropdown.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }],
    execute: function () {
      HeaderSecondary = function (_Component) {
        babelHelpers.inherits(HeaderSecondary, _Component);

        function HeaderSecondary() {
          babelHelpers.classCallCheck(this, HeaderSecondary);
          return babelHelpers.possibleConstructorReturn(this, (HeaderSecondary.__proto__ || Object.getPrototypeOf(HeaderSecondary)).apply(this, arguments));
        }

        babelHelpers.createClass(HeaderSecondary, [{
          key: 'view',
          value: function view() {
            return m(
              'ul',
              { className: 'Header-controls' },
              listItems(this.items().toArray())
            );
          }
        }, {
          key: 'config',
          value: function config(isInitialized, context) {
            // Since this component is 'above' the content of the page (that is, it is a
            // part of the global UI that persists between routes), we will flag the DOM
            // to be retained across route changes.
            context.retain = true;
          }
        }, {
          key: 'items',
          value: function items() {
            var items = new ItemList();

            items.add('search', app.search.render(), 30);

            if (app.forum.attribute("showLanguageSelector") && Object.keys(app.data.locales).length > 1) {
              var locales = [];

              var _loop = function _loop(locale) {
                locales.push(Button.component({
                  active: app.data.locale === locale,
                  children: app.data.locales[locale],
                  icon: app.data.locale === locale ? 'check' : true,
                  onclick: function onclick() {
                    if (app.session.user) {
                      app.session.user.savePreferences({ locale: locale }).then(function () {
                        return window.location.reload();
                      });
                    } else {
                      document.cookie = 'locale=' + locale + '; path=/; expires=Tue, 19 Jan 2038 03:14:07 GMT';
                      window.location.reload();
                    }
                  }
                }));
              };

              for (var locale in app.data.locales) {
                _loop(locale);
              }

              items.add('locale', SelectDropdown.component({
                children: locales,
                buttonClassName: 'Button Button--link'
              }), 20);
            }

            if (app.session.user) {
              items.add('notifications', NotificationsDropdown.component(), 10);
              items.add('session', SessionDropdown.component(), 0);
            } else {
              if (app.forum.attribute('allowSignUp')) {
                items.add('signUp', Button.component({
                  children: app.translator.trans('core.forum.header.sign_up_link'),
                  className: 'Button Button--link',
                  onclick: function onclick() {
                    return app.modal.show(new SignUpModal());
                  }
                }), 10);
              }

              items.add('logIn', Button.component({
                children: app.translator.trans('core.forum.header.log_in_link'),
                className: 'Button Button--link',
                onclick: function onclick() {
                  return app.modal.show(new LogInModal());
                }
              }), 0);
            }

            return items;
          }
        }]);
        return HeaderSecondary;
      }(Component);

      _export('default', HeaderSecondary);
    }
  };
});;
'use strict';

System.register('flarum/components/IndexPage', ['flarum/extend', 'flarum/components/Page', 'flarum/utils/ItemList', 'flarum/helpers/listItems', 'flarum/helpers/icon', 'flarum/components/DiscussionList', 'flarum/components/WelcomeHero', 'flarum/components/DiscussionComposer', 'flarum/components/LogInModal', 'flarum/components/DiscussionPage', 'flarum/components/Select', 'flarum/components/Button', 'flarum/components/LinkButton', 'flarum/components/SelectDropdown'], function (_export, _context) {
  "use strict";

  var extend, Page, ItemList, listItems, icon, DiscussionList, WelcomeHero, DiscussionComposer, LogInModal, DiscussionPage, Select, Button, LinkButton, SelectDropdown, IndexPage;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
    }, function (_flarumComponentsPage) {
      Page = _flarumComponentsPage.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }, function (_flarumComponentsDiscussionList) {
      DiscussionList = _flarumComponentsDiscussionList.default;
    }, function (_flarumComponentsWelcomeHero) {
      WelcomeHero = _flarumComponentsWelcomeHero.default;
    }, function (_flarumComponentsDiscussionComposer) {
      DiscussionComposer = _flarumComponentsDiscussionComposer.default;
    }, function (_flarumComponentsLogInModal) {
      LogInModal = _flarumComponentsLogInModal.default;
    }, function (_flarumComponentsDiscussionPage) {
      DiscussionPage = _flarumComponentsDiscussionPage.default;
    }, function (_flarumComponentsSelect) {
      Select = _flarumComponentsSelect.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumComponentsLinkButton) {
      LinkButton = _flarumComponentsLinkButton.default;
    }, function (_flarumComponentsSelectDropdown) {
      SelectDropdown = _flarumComponentsSelectDropdown.default;
    }],
    execute: function () {
      IndexPage = function (_Page) {
        babelHelpers.inherits(IndexPage, _Page);

        function IndexPage() {
          babelHelpers.classCallCheck(this, IndexPage);
          return babelHelpers.possibleConstructorReturn(this, (IndexPage.__proto__ || Object.getPrototypeOf(IndexPage)).apply(this, arguments));
        }

        babelHelpers.createClass(IndexPage, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(IndexPage.prototype.__proto__ || Object.getPrototypeOf(IndexPage.prototype), 'init', this).call(this);

            // If the user is returning from a discussion page, then take note of which
            // discussion they have just visited. After the view is rendered, we will
            // scroll down so that this discussion is in view.
            if (app.previous instanceof DiscussionPage) {
              this.lastDiscussion = app.previous.discussion;
            }

            // If the user is coming from the discussion list, then they have either
            // just switched one of the parameters (filter, sort, search) or they
            // probably want to refresh the results. We will clear the discussion list
            // cache so that results are reloaded.
            if (app.previous instanceof IndexPage) {
              app.cache.discussionList = null;
            }

            var params = this.params();

            if (app.cache.discussionList) {
              // Compare the requested parameters (sort, search query) to the ones that
              // are currently present in the cached discussion list. If they differ, we
              // will clear the cache and set up a new discussion list component with
              // the new parameters.
              Object.keys(params).some(function (key) {
                if (app.cache.discussionList.props.params[key] !== params[key]) {
                  app.cache.discussionList = null;
                  return true;
                }
              });
            }

            if (!app.cache.discussionList) {
              app.cache.discussionList = new DiscussionList({ params: params });
            }

            app.history.push('index', icon('bars'));

            this.bodyClass = 'App--index';
          }
        }, {
          key: 'onunload',
          value: function onunload() {
            // Save the scroll position so we can restore it when we return to the
            // discussion list.
            app.cache.scrollTop = $(window).scrollTop();
          }
        }, {
          key: 'view',
          value: function view() {
            return m(
              'div',
              { className: 'IndexPage' },
              this.hero(),
              m(
                'div',
                { className: 'container' },
                m(
                  'nav',
                  { className: 'IndexPage-nav sideNav' },
                  m(
                    'ul',
                    null,
                    listItems(this.sidebarItems().toArray())
                  )
                ),
                m(
                  'div',
                  { className: 'IndexPage-results sideNavOffset' },
                  m(
                    'div',
                    { className: 'IndexPage-toolbar' },
                    m(
                      'ul',
                      { className: 'IndexPage-toolbar-view' },
                      listItems(this.viewItems().toArray())
                    ),
                    m(
                      'ul',
                      { className: 'IndexPage-toolbar-action' },
                      listItems(this.actionItems().toArray())
                    )
                  ),
                  app.cache.discussionList.render()
                )
              )
            );
          }
        }, {
          key: 'config',
          value: function config(isInitialized, context) {
            babelHelpers.get(IndexPage.prototype.__proto__ || Object.getPrototypeOf(IndexPage.prototype), 'config', this).apply(this, arguments);

            if (isInitialized) return;

            extend(context, 'onunload', function () {
              return $('#app').css('min-height', '');
            });

            app.setTitle('');
            app.setTitleCount(0);

            // Work out the difference between the height of this hero and that of the
            // previous hero. Maintain the same scroll position relative to the bottom
            // of the hero so that the sidebar doesn't jump around.
            var oldHeroHeight = app.cache.heroHeight;
            var heroHeight = app.cache.heroHeight = this.$('.Hero').outerHeight();
            var scrollTop = app.cache.scrollTop;

            $('#app').css('min-height', $(window).height() + heroHeight);

            // Scroll to the remembered position. We do this after a short delay so that
            // it happens after the browser has done its own "back button" scrolling,
            // which isn't right. https://github.com/flarum/core/issues/835
            var scroll = function scroll() {
              return $(window).scrollTop(scrollTop - oldHeroHeight + heroHeight);
            };
            scroll();
            setTimeout(scroll, 1);

            // If we've just returned from a discussion page, then the constructor will
            // have set the `lastDiscussion` property. If this is the case, we want to
            // scroll down to that discussion so that it's in view.
            if (this.lastDiscussion) {
              var $discussion = this.$('.DiscussionListItem[data-id="' + this.lastDiscussion.id() + '"]');

              if ($discussion.length) {
                var indexTop = $('#header').outerHeight();
                var indexBottom = $(window).height();
                var discussionTop = $discussion.offset().top;
                var discussionBottom = discussionTop + $discussion.outerHeight();

                if (discussionTop < scrollTop + indexTop || discussionBottom > scrollTop + indexBottom) {
                  $(window).scrollTop(discussionTop - indexTop);
                }
              }
            }
          }
        }, {
          key: 'hero',
          value: function hero() {
            return WelcomeHero.component();
          }
        }, {
          key: 'sidebarItems',
          value: function sidebarItems() {
            var items = new ItemList();
            var canStartDiscussion = app.forum.attribute('canStartDiscussion') || !app.session.user;

            items.add('newDiscussion', Button.component({
              children: app.translator.trans(canStartDiscussion ? 'core.forum.index.start_discussion_button' : 'core.forum.index.cannot_start_discussion_button'),
              icon: 'edit',
              className: 'Button Button--primary IndexPage-newDiscussion',
              itemClassName: 'App-primaryControl',
              onclick: this.newDiscussion.bind(this),
              disabled: !canStartDiscussion
            }));

            items.add('nav', SelectDropdown.component({
              children: this.navItems(this).toArray(),
              buttonClassName: 'Button',
              className: 'App-titleControl'
            }));

            return items;
          }
        }, {
          key: 'navItems',
          value: function navItems() {
            var items = new ItemList();
            var params = this.stickyParams();

            items.add('allDiscussions', LinkButton.component({
              href: app.route('index', params),
              children: app.translator.trans('core.forum.index.all_discussions_link'),
              icon: 'comments-o'
            }), 100);

            return items;
          }
        }, {
          key: 'viewItems',
          value: function viewItems() {
            var items = new ItemList();
            var sortMap = app.cache.discussionList.sortMap();

            var sortOptions = {};
            for (var i in sortMap) {
              sortOptions[i] = app.translator.trans('core.forum.index_sort.' + i + '_button');
            }

            items.add('sort', Select.component({
              options: sortOptions,
              value: this.params().sort || Object.keys(sortMap)[0],
              onchange: this.changeSort.bind(this)
            }));

            return items;
          }
        }, {
          key: 'actionItems',
          value: function actionItems() {
            var items = new ItemList();

            items.add('refresh', Button.component({
              title: app.translator.trans('core.forum.index.refresh_tooltip'),
              icon: 'refresh',
              className: 'Button Button--icon',
              onclick: function onclick() {
                app.cache.discussionList.refresh();
                if (app.session.user) {
                  app.store.find('users', app.session.user.id());
                  m.redraw();
                }
              }
            }));

            if (app.session.user) {
              items.add('markAllAsRead', Button.component({
                title: app.translator.trans('core.forum.index.mark_all_as_read_tooltip'),
                icon: 'check',
                className: 'Button Button--icon',
                onclick: this.markAllAsRead.bind(this)
              }));
            }

            return items;
          }
        }, {
          key: 'searching',
          value: function searching() {
            return this.params().q;
          }
        }, {
          key: 'clearSearch',
          value: function clearSearch() {
            var params = this.params();
            delete params.q;

            m.route(app.route(this.props.routeName, params));
          }
        }, {
          key: 'changeSort',
          value: function changeSort(sort) {
            var params = this.params();

            if (sort === Object.keys(app.cache.discussionList.sortMap())[0]) {
              delete params.sort;
            } else {
              params.sort = sort;
            }

            m.route(app.route(this.props.routeName, params));
          }
        }, {
          key: 'stickyParams',
          value: function stickyParams() {
            return {
              sort: m.route.param('sort'),
              q: m.route.param('q')
            };
          }
        }, {
          key: 'params',
          value: function params() {
            var params = this.stickyParams();

            params.filter = m.route.param('filter');

            return params;
          }
        }, {
          key: 'newDiscussion',
          value: function newDiscussion() {
            var deferred = m.deferred();

            if (app.session.user) {
              this.composeNewDiscussion(deferred);
            } else {
              app.modal.show(new LogInModal({
                onlogin: this.composeNewDiscussion.bind(this, deferred)
              }));
            }

            return deferred.promise;
          }
        }, {
          key: 'composeNewDiscussion',
          value: function composeNewDiscussion(deferred) {
            var component = new DiscussionComposer({ user: app.session.user });

            app.composer.load(component);
            app.composer.show();

            deferred.resolve(component);

            return deferred.promise;
          }
        }, {
          key: 'markAllAsRead',
          value: function markAllAsRead() {
            var confirmation = confirm(app.translator.trans('core.forum.index.mark_all_as_read_confirmation'));

            if (confirmation) {
              app.session.user.save({ readTime: new Date() });
            }
          }
        }]);
        return IndexPage;
      }(Page);

      _export('default', IndexPage);
    }
  };
});;
'use strict';

System.register('flarum/components/LinkButton', ['flarum/components/Button'], function (_export, _context) {
  "use strict";

  var Button, LinkButton;
  return {
    setters: [function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }],
    execute: function () {
      LinkButton = function (_Button) {
        babelHelpers.inherits(LinkButton, _Button);

        function LinkButton() {
          babelHelpers.classCallCheck(this, LinkButton);
          return babelHelpers.possibleConstructorReturn(this, (LinkButton.__proto__ || Object.getPrototypeOf(LinkButton)).apply(this, arguments));
        }

        babelHelpers.createClass(LinkButton, [{
          key: 'view',
          value: function view() {
            var vdom = babelHelpers.get(LinkButton.prototype.__proto__ || Object.getPrototypeOf(LinkButton.prototype), 'view', this).call(this);

            vdom.tag = 'a';

            return vdom;
          }
        }], [{
          key: 'initProps',
          value: function initProps(props) {
            props.active = this.isActive(props);
            props.config = props.config || m.route;
          }
        }, {
          key: 'isActive',
          value: function isActive(props) {
            return typeof props.active !== 'undefined' ? props.active : m.route() === props.href;
          }
        }]);
        return LinkButton;
      }(Button);

      _export('default', LinkButton);
    }
  };
});;
'use strict';

System.register('flarum/components/LoadingIndicator', ['flarum/Component'], function (_export, _context) {
  "use strict";

  var Component, LoadingIndicator;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }],
    execute: function () {
      LoadingIndicator = function (_Component) {
        babelHelpers.inherits(LoadingIndicator, _Component);

        function LoadingIndicator() {
          babelHelpers.classCallCheck(this, LoadingIndicator);
          return babelHelpers.possibleConstructorReturn(this, (LoadingIndicator.__proto__ || Object.getPrototypeOf(LoadingIndicator)).apply(this, arguments));
        }

        babelHelpers.createClass(LoadingIndicator, [{
          key: 'view',
          value: function view() {
            var attrs = babelHelpers.extends({}, this.props);

            attrs.className = 'LoadingIndicator ' + (attrs.className || '');
            delete attrs.size;

            return m(
              'div',
              attrs,
              m.trust('&nbsp;')
            );
          }
        }, {
          key: 'config',
          value: function config() {
            var size = this.props.size || 'small';

            $.fn.spin.presets[size].zIndex = 'auto';
            this.$().spin(size);
          }
        }]);
        return LoadingIndicator;
      }(Component);

      _export('default', LoadingIndicator);
    }
  };
});;
'use strict';

System.register('flarum/components/LoadingPost', ['flarum/Component', 'flarum/helpers/avatar'], function (_export, _context) {
  "use strict";

  var Component, avatar, LoadingPost;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumHelpersAvatar) {
      avatar = _flarumHelpersAvatar.default;
    }],
    execute: function () {
      LoadingPost = function (_Component) {
        babelHelpers.inherits(LoadingPost, _Component);

        function LoadingPost() {
          babelHelpers.classCallCheck(this, LoadingPost);
          return babelHelpers.possibleConstructorReturn(this, (LoadingPost.__proto__ || Object.getPrototypeOf(LoadingPost)).apply(this, arguments));
        }

        babelHelpers.createClass(LoadingPost, [{
          key: 'view',
          value: function view() {
            return m(
              'div',
              { className: 'Post CommentPost LoadingPost' },
              m(
                'header',
                { className: 'Post-header' },
                avatar(null, { className: 'PostUser-avatar' }),
                m('div', { className: 'fakeText' })
              ),
              m(
                'div',
                { className: 'Post-body' },
                m('div', { className: 'fakeText' }),
                m('div', { className: 'fakeText' }),
                m('div', { className: 'fakeText' })
              )
            );
          }
        }]);
        return LoadingPost;
      }(Component);

      _export('default', LoadingPost);
    }
  };
});;
'use strict';

System.register('flarum/components/LogInButton', ['flarum/components/Button'], function (_export, _context) {
  "use strict";

  var Button, LogInButton;
  return {
    setters: [function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }],
    execute: function () {
      LogInButton = function (_Button) {
        babelHelpers.inherits(LogInButton, _Button);

        function LogInButton() {
          babelHelpers.classCallCheck(this, LogInButton);
          return babelHelpers.possibleConstructorReturn(this, (LogInButton.__proto__ || Object.getPrototypeOf(LogInButton)).apply(this, arguments));
        }

        babelHelpers.createClass(LogInButton, null, [{
          key: 'initProps',
          value: function initProps(props) {
            props.className = (props.className || '') + ' LogInButton';

            props.onclick = function () {
              var width = 600;
              var height = 400;
              var $window = $(window);

              window.open(app.forum.attribute('baseUrl') + props.path, 'logInPopup', 'width=' + width + ',' + ('height=' + height + ',') + ('top=' + ($window.height() / 2 - height / 2) + ',') + ('left=' + ($window.width() / 2 - width / 2) + ',') + 'status=no,scrollbars=no,resizable=no');
            };

            babelHelpers.get(LogInButton.__proto__ || Object.getPrototypeOf(LogInButton), 'initProps', this).call(this, props);
          }
        }]);
        return LogInButton;
      }(Button);

      _export('default', LogInButton);
    }
  };
});;
'use strict';

System.register('flarum/components/LogInButtons', ['flarum/Component', 'flarum/utils/ItemList'], function (_export, _context) {
  "use strict";

  var Component, ItemList, LogInButtons;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }],
    execute: function () {
      LogInButtons = function (_Component) {
        babelHelpers.inherits(LogInButtons, _Component);

        function LogInButtons() {
          babelHelpers.classCallCheck(this, LogInButtons);
          return babelHelpers.possibleConstructorReturn(this, (LogInButtons.__proto__ || Object.getPrototypeOf(LogInButtons)).apply(this, arguments));
        }

        babelHelpers.createClass(LogInButtons, [{
          key: 'view',
          value: function view() {
            return m(
              'div',
              { className: 'LogInButtons' },
              this.items().toArray()
            );
          }
        }, {
          key: 'items',
          value: function items() {
            return new ItemList();
          }
        }]);
        return LogInButtons;
      }(Component);

      _export('default', LogInButtons);
    }
  };
});;
'use strict';

System.register('flarum/components/LogInModal', ['flarum/components/Modal', 'flarum/components/ForgotPasswordModal', 'flarum/components/SignUpModal', 'flarum/components/Alert', 'flarum/components/Button', 'flarum/components/LogInButtons', 'flarum/components/Switch', 'flarum/utils/extractText'], function (_export, _context) {
  "use strict";

  var Modal, ForgotPasswordModal, SignUpModal, Alert, Button, LogInButtons, Switch, extractText, LogInModal;
  return {
    setters: [function (_flarumComponentsModal) {
      Modal = _flarumComponentsModal.default;
    }, function (_flarumComponentsForgotPasswordModal) {
      ForgotPasswordModal = _flarumComponentsForgotPasswordModal.default;
    }, function (_flarumComponentsSignUpModal) {
      SignUpModal = _flarumComponentsSignUpModal.default;
    }, function (_flarumComponentsAlert) {
      Alert = _flarumComponentsAlert.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumComponentsLogInButtons) {
      LogInButtons = _flarumComponentsLogInButtons.default;
    }, function (_flarumComponentsSwitch) {
      Switch = _flarumComponentsSwitch.default;
    }, function (_flarumUtilsExtractText) {
      extractText = _flarumUtilsExtractText.default;
    }],
    execute: function () {
      LogInModal = function (_Modal) {
        babelHelpers.inherits(LogInModal, _Modal);

        function LogInModal() {
          babelHelpers.classCallCheck(this, LogInModal);
          return babelHelpers.possibleConstructorReturn(this, (LogInModal.__proto__ || Object.getPrototypeOf(LogInModal)).apply(this, arguments));
        }

        babelHelpers.createClass(LogInModal, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(LogInModal.prototype.__proto__ || Object.getPrototypeOf(LogInModal.prototype), 'init', this).call(this);

            /**
             * The value of the identification input.
             *
             * @type {Function}
             */
            this.identification = m.prop(this.props.identification || '');

            /**
             * The value of the password input.
             *
             * @type {Function}
             */
            this.password = m.prop(this.props.password || '');

            /**
             * The value of the remember me input.
             *
             * @type {Function}
             */
            this.remember = m.prop(this.props.remember && true);
          }
        }, {
          key: 'className',
          value: function className() {
            return 'LogInModal Modal--small';
          }
        }, {
          key: 'title',
          value: function title() {
            return app.translator.trans('core.forum.log_in.title');
          }
        }, {
          key: 'content',
          value: function content() {
            return [m(
              'div',
              { className: 'Modal-body' },
              m(LogInButtons, null),
              m(
                'div',
                { className: 'Form Form--centered' },
                m(
                  'div',
                  { className: 'Form-group' },
                  m('input', { className: 'FormControl', name: 'identification', type: 'text', placeholder: extractText(app.translator.trans('core.forum.log_in.username_or_email_placeholder')),
                    bidi: this.identification,
                    disabled: this.loading })
                ),
                m(
                  'div',
                  { className: 'Form-group' },
                  m('input', { className: 'FormControl', name: 'password', type: 'password', placeholder: extractText(app.translator.trans('core.forum.log_in.password_placeholder')),
                    bidi: this.password,
                    disabled: this.loading })
                ),
                m(
                  'div',
                  { className: 'Form-group' },
                  Switch.component({
                    children: app.translator.trans('core.forum.log_in.remember_me_label'),
                    disabled: this.loading,
                    onchange: this.remember,
                    state: this.remember()
                  })
                ),
                m(
                  'div',
                  { className: 'Form-group' },
                  Button.component({
                    className: 'Button Button--primary Button--block',
                    type: 'submit',
                    loading: this.loading,
                    children: app.translator.trans('core.forum.log_in.submit_button')
                  })
                )
              )
            ), m(
              'div',
              { className: 'Modal-footer' },
              m(
                'p',
                { className: 'LogInModal-forgotPassword' },
                m(
                  'a',
                  { onclick: this.forgotPassword.bind(this) },
                  app.translator.trans('core.forum.log_in.forgot_password_link')
                )
              ),
              app.forum.attribute('allowSignUp') ? m(
                'p',
                { className: 'LogInModal-signUp' },
                app.translator.trans('core.forum.log_in.sign_up_text', { a: m('a', { onclick: this.signUp.bind(this) }) })
              ) : ''
            )];
          }
        }, {
          key: 'forgotPassword',
          value: function forgotPassword() {
            var email = this.identification();
            var props = email.indexOf('@') !== -1 ? { email: email } : undefined;

            app.modal.show(new ForgotPasswordModal(props));
          }
        }, {
          key: 'signUp',
          value: function signUp() {
            var props = { password: this.password() };
            var identification = this.identification();
            props[identification.indexOf('@') !== -1 ? 'email' : 'username'] = identification;

            app.modal.show(new SignUpModal(props));
          }
        }, {
          key: 'onready',
          value: function onready() {
            this.$('[name=' + (this.identification() ? 'password' : 'identification') + ']').select();
          }
        }, {
          key: 'onsubmit',
          value: function onsubmit(e) {
            e.preventDefault();

            this.loading = true;

            var identification = this.identification();
            var password = this.password();
            var remember = this.remember();

            app.session.login({ identification: identification, password: password, remember: remember }, { errorHandler: this.onerror.bind(this) }).then(function () {
              return window.location.reload();
            }, this.loaded.bind(this));
          }
        }, {
          key: 'onerror',
          value: function onerror(error) {
            if (error.status === 401) {
              error.alert.props.children = app.translator.trans('core.forum.log_in.invalid_login_message');
            }

            babelHelpers.get(LogInModal.prototype.__proto__ || Object.getPrototypeOf(LogInModal.prototype), 'onerror', this).call(this, error);
          }
        }]);
        return LogInModal;
      }(Modal);

      _export('default', LogInModal);
    }
  };
});;
'use strict';

System.register('flarum/components/Modal', ['flarum/Component', 'flarum/components/Alert', 'flarum/components/Button'], function (_export, _context) {
  "use strict";

  var Component, Alert, Button, Modal;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumComponentsAlert) {
      Alert = _flarumComponentsAlert.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }],
    execute: function () {
      Modal = function (_Component) {
        babelHelpers.inherits(Modal, _Component);

        function Modal() {
          babelHelpers.classCallCheck(this, Modal);
          return babelHelpers.possibleConstructorReturn(this, (Modal.__proto__ || Object.getPrototypeOf(Modal)).apply(this, arguments));
        }

        babelHelpers.createClass(Modal, [{
          key: 'init',
          value: function init() {
            /**
             * An alert component to show below the header.
             *
             * @type {Alert}
             */
            this.alert = null;
          }
        }, {
          key: 'view',
          value: function view() {
            if (this.alert) {
              this.alert.props.dismissible = false;
            }

            return m(
              'div',
              { className: 'Modal modal-dialog ' + this.className() },
              m(
                'div',
                { className: 'Modal-content' },
                this.isDismissible() ? m(
                  'div',
                  { className: 'Modal-close App-backControl' },
                  Button.component({
                    icon: 'times',
                    onclick: this.hide.bind(this),
                    className: 'Button Button--icon Button--link'
                  })
                ) : '',
                m(
                  'form',
                  { onsubmit: this.onsubmit.bind(this) },
                  m(
                    'div',
                    { className: 'Modal-header' },
                    m(
                      'h3',
                      { className: 'App-titleControl App-titleControl--text' },
                      this.title()
                    )
                  ),
                  alert ? m(
                    'div',
                    { className: 'Modal-alert' },
                    this.alert
                  ) : '',
                  this.content()
                )
              )
            );
          }
        }, {
          key: 'isDismissible',
          value: function isDismissible() {
            return true;
          }
        }, {
          key: 'className',
          value: function className() {}
        }, {
          key: 'title',
          value: function title() {}
        }, {
          key: 'content',
          value: function content() {}
        }, {
          key: 'onsubmit',
          value: function onsubmit() {}
        }, {
          key: 'onready',
          value: function onready() {
            this.$('form').find('input, select, textarea').first().focus().select();
          }
        }, {
          key: 'onhide',
          value: function onhide() {}
        }, {
          key: 'hide',
          value: function hide() {
            app.modal.close();
          }
        }, {
          key: 'loaded',
          value: function loaded() {
            this.loading = false;
            m.redraw();
          }
        }, {
          key: 'onerror',
          value: function onerror(error) {
            this.alert = error.alert;

            m.redraw();

            if (error.status === 422 && error.response.errors) {
              this.$('form [name=' + error.response.errors[0].source.pointer.replace('/data/attributes/', '') + ']').select();
            } else {
              this.onready();
            }
          }
        }]);
        return Modal;
      }(Component);

      _export('default', Modal);
    }
  };
});;
'use strict';

System.register('flarum/components/ModalManager', ['flarum/Component', 'flarum/components/Modal'], function (_export, _context) {
  "use strict";

  var Component, Modal, ModalManager;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumComponentsModal) {
      Modal = _flarumComponentsModal.default;
    }],
    execute: function () {
      ModalManager = function (_Component) {
        babelHelpers.inherits(ModalManager, _Component);

        function ModalManager() {
          babelHelpers.classCallCheck(this, ModalManager);
          return babelHelpers.possibleConstructorReturn(this, (ModalManager.__proto__ || Object.getPrototypeOf(ModalManager)).apply(this, arguments));
        }

        babelHelpers.createClass(ModalManager, [{
          key: 'init',
          value: function init() {
            this.showing = false;
            this.component = null;
          }
        }, {
          key: 'view',
          value: function view() {
            return m(
              'div',
              { className: 'ModalManager modal fade' },
              this.component && this.component.render()
            );
          }
        }, {
          key: 'config',
          value: function config(isInitialized, context) {
            if (isInitialized) return;

            // Since this component is 'above' the content of the page (that is, it is a
            // part of the global UI that persists between routes), we will flag the DOM
            // to be retained across route changes.
            context.retain = true;

            this.$().on('hidden.bs.modal', this.clear.bind(this)).on('shown.bs.modal', this.onready.bind(this));
          }
        }, {
          key: 'show',
          value: function show(component) {
            if (!(component instanceof Modal)) {
              throw new Error('The ModalManager component can only show Modal components');
            }

            clearTimeout(this.hideTimeout);

            this.showing = true;
            this.component = component;

            app.current.retain = true;

            m.redraw(true);

            this.$().modal({ backdrop: this.component.isDismissible() ? true : 'static' }).modal('show');
            this.onready();
          }
        }, {
          key: 'close',
          value: function close() {
            var _this2 = this;

            if (!this.showing) return;

            // Don't hide the modal immediately, because if the consumer happens to call
            // the `show` method straight after to show another modal dialog, it will
            // cause Bootstrap's modal JS to misbehave. Instead we will wait for a tiny
            // bit to give the `show` method the opportunity to prevent this from going
            // ahead.
            this.hideTimeout = setTimeout(function () {
              _this2.$().modal('hide');
              _this2.showing = false;
            });
          }
        }, {
          key: 'clear',
          value: function clear() {
            if (this.component) {
              this.component.onhide();
            }

            this.component = null;

            app.current.retain = false;

            m.lazyRedraw();
          }
        }, {
          key: 'onready',
          value: function onready() {
            if (this.component && this.component.onready) {
              this.component.onready(this.$());
            }
          }
        }]);
        return ModalManager;
      }(Component);

      _export('default', ModalManager);
    }
  };
});;
'use strict';

System.register('flarum/components/Navigation', ['flarum/Component', 'flarum/components/Button', 'flarum/components/LinkButton'], function (_export, _context) {
  "use strict";

  var Component, Button, LinkButton, Navigation;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumComponentsLinkButton) {
      LinkButton = _flarumComponentsLinkButton.default;
    }],
    execute: function () {
      Navigation = function (_Component) {
        babelHelpers.inherits(Navigation, _Component);

        function Navigation() {
          babelHelpers.classCallCheck(this, Navigation);
          return babelHelpers.possibleConstructorReturn(this, (Navigation.__proto__ || Object.getPrototypeOf(Navigation)).apply(this, arguments));
        }

        babelHelpers.createClass(Navigation, [{
          key: 'view',
          value: function view() {
            var _app = app,
                history = _app.history,
                pane = _app.pane;


            return m(
              'div',
              { className: 'Navigation ButtonGroup ' + (this.props.className || ''),
                onmouseenter: pane && pane.show.bind(pane),
                onmouseleave: pane && pane.onmouseleave.bind(pane) },
              history.canGoBack() ? [this.getBackButton(), this.getPaneButton()] : this.getDrawerButton()
            );
          }
        }, {
          key: 'config',
          value: function config(isInitialized, context) {
            // Since this component is 'above' the content of the page (that is, it is a
            // part of the global UI that persists between routes), we will flag the DOM
            // to be retained across route changes.
            context.retain = true;
          }
        }, {
          key: 'getBackButton',
          value: function getBackButton() {
            var _app2 = app,
                history = _app2.history;

            var previous = history.getPrevious() || {};

            return LinkButton.component({
              className: 'Button Navigation-back ' + (previous.title ? '' : 'Button--icon'),
              href: history.backUrl(),
              icon: 'chevron-left',
              children: previous.title,
              config: function config() {},
              onclick: function onclick(e) {
                if (e.shiftKey || e.ctrlKey || e.metaKey || e.which === 2) return;
                e.preventDefault();
                history.back();
              }
            });
          }
        }, {
          key: 'getPaneButton',
          value: function getPaneButton() {
            var _app3 = app,
                pane = _app3.pane;


            if (!pane || !pane.active) return '';

            return Button.component({
              className: 'Button Button--icon Navigation-pin' + (pane.pinned ? ' active' : ''),
              onclick: pane.togglePinned.bind(pane),
              icon: 'thumb-tack'
            });
          }
        }, {
          key: 'getDrawerButton',
          value: function getDrawerButton() {
            if (!this.props.drawer) return '';

            var _app4 = app,
                drawer = _app4.drawer;

            var user = app.session.user;

            return Button.component({
              className: 'Button Button--icon Navigation-drawer' + (user && user.newNotificationsCount() ? ' new' : ''),
              onclick: function onclick(e) {
                e.stopPropagation();
                drawer.show();
              },
              icon: 'reorder'
            });
          }
        }]);
        return Navigation;
      }(Component);

      _export('default', Navigation);
    }
  };
});;
'use strict';

System.register('flarum/components/Notification', ['flarum/Component', 'flarum/helpers/avatar', 'flarum/helpers/icon', 'flarum/helpers/humanTime'], function (_export, _context) {
  "use strict";

  var Component, avatar, icon, humanTime, Notification;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumHelpersAvatar) {
      avatar = _flarumHelpersAvatar.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }, function (_flarumHelpersHumanTime) {
      humanTime = _flarumHelpersHumanTime.default;
    }],
    execute: function () {
      Notification = function (_Component) {
        babelHelpers.inherits(Notification, _Component);

        function Notification() {
          babelHelpers.classCallCheck(this, Notification);
          return babelHelpers.possibleConstructorReturn(this, (Notification.__proto__ || Object.getPrototypeOf(Notification)).apply(this, arguments));
        }

        babelHelpers.createClass(Notification, [{
          key: 'view',
          value: function view() {
            var notification = this.props.notification;
            var href = this.href();

            return m(
              'a',
              { className: 'Notification Notification--' + notification.contentType() + ' ' + (!notification.isRead() ? 'unread' : ''),
                href: href,
                config: function config(element, isInitialized) {
                  if (href.indexOf('://') === -1) m.route.apply(this, arguments);

                  if (!isInitialized) $(element).click(this.markAsRead.bind(this));
                } },
              avatar(notification.sender()),
              icon(this.icon(), { className: 'Notification-icon' }),
              m(
                'span',
                { className: 'Notification-content' },
                this.content()
              ),
              humanTime(notification.time()),
              m(
                'div',
                { className: 'Notification-excerpt' },
                this.excerpt()
              )
            );
          }
        }, {
          key: 'icon',
          value: function icon() {}
        }, {
          key: 'href',
          value: function href() {}
        }, {
          key: 'content',
          value: function content() {}
        }, {
          key: 'excerpt',
          value: function excerpt() {}
        }, {
          key: 'markAsRead',
          value: function markAsRead() {
            if (this.props.notification.isRead()) return;

            app.session.user.pushAttributes({ unreadNotificationsCount: app.session.user.unreadNotificationsCount() - 1 });

            this.props.notification.save({ isRead: true });
          }
        }]);
        return Notification;
      }(Component);

      _export('default', Notification);
    }
  };
});;
'use strict';

System.register('flarum/components/NotificationGrid', ['flarum/Component', 'flarum/components/Checkbox', 'flarum/helpers/icon', 'flarum/utils/ItemList'], function (_export, _context) {
  "use strict";

  var Component, Checkbox, icon, ItemList, NotificationGrid;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumComponentsCheckbox) {
      Checkbox = _flarumComponentsCheckbox.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }],
    execute: function () {
      NotificationGrid = function (_Component) {
        babelHelpers.inherits(NotificationGrid, _Component);

        function NotificationGrid() {
          babelHelpers.classCallCheck(this, NotificationGrid);
          return babelHelpers.possibleConstructorReturn(this, (NotificationGrid.__proto__ || Object.getPrototypeOf(NotificationGrid)).apply(this, arguments));
        }

        babelHelpers.createClass(NotificationGrid, [{
          key: 'init',
          value: function init() {
            var _this2 = this;

            /**
             * Information about the available notification methods.
             *
             * @type {Array}
             */
            this.methods = [{ name: 'alert', icon: 'bell', label: app.translator.trans('core.forum.settings.notify_by_web_heading') }, { name: 'email', icon: 'envelope-o', label: app.translator.trans('core.forum.settings.notify_by_email_heading') }];

            /**
             * A map of notification type-method combinations to the checkbox instances
             * that represent them.
             *
             * @type {Object}
             */
            this.inputs = {};

            /**
             * Information about the available notification types.
             *
             * @type {Object}
             */
            this.types = this.notificationTypes().toArray();

            // For each of the notification type-method combinations, create and store a
            // new checkbox component instance, which we will render in the view.
            this.types.forEach(function (type) {
              _this2.methods.forEach(function (method) {
                var key = _this2.preferenceKey(type.name, method.name);
                var preference = _this2.props.user.preferences()[key];

                _this2.inputs[key] = new Checkbox({
                  state: !!preference,
                  disabled: typeof preference === 'undefined',
                  onchange: function onchange() {
                    return _this2.toggle([key]);
                  }
                });
              });
            });
          }
        }, {
          key: 'view',
          value: function view() {
            var _this3 = this;

            return m(
              'table',
              { className: 'NotificationGrid' },
              m(
                'thead',
                null,
                m(
                  'tr',
                  null,
                  m('td', null),
                  this.methods.map(function (method) {
                    return m(
                      'th',
                      { className: 'NotificationGrid-groupToggle', onclick: _this3.toggleMethod.bind(_this3, method.name) },
                      icon(method.icon),
                      ' ',
                      method.label
                    );
                  })
                )
              ),
              m(
                'tbody',
                null,
                this.types.map(function (type) {
                  return m(
                    'tr',
                    null,
                    m(
                      'td',
                      { className: 'NotificationGrid-groupToggle', onclick: _this3.toggleType.bind(_this3, type.name) },
                      icon(type.icon),
                      ' ',
                      type.label
                    ),
                    _this3.methods.map(function (method) {
                      return m(
                        'td',
                        { className: 'NotificationGrid-checkbox' },
                        _this3.inputs[_this3.preferenceKey(type.name, method.name)].render()
                      );
                    })
                  );
                })
              )
            );
          }
        }, {
          key: 'config',
          value: function config(isInitialized) {
            if (isInitialized) return;

            this.$('thead .NotificationGrid-groupToggle').bind('mouseenter mouseleave', function (e) {
              var i = parseInt($(this).index(), 10) + 1;
              $(this).parents('table').find('td:nth-child(' + i + ')').toggleClass('highlighted', e.type === 'mouseenter');
            });

            this.$('tbody .NotificationGrid-groupToggle').bind('mouseenter mouseleave', function (e) {
              $(this).parent().find('td').toggleClass('highlighted', e.type === 'mouseenter');
            });
          }
        }, {
          key: 'toggle',
          value: function toggle(keys) {
            var _this4 = this;

            var user = this.props.user;
            var preferences = user.preferences();
            var enabled = !preferences[keys[0]];

            keys.forEach(function (key) {
              var control = _this4.inputs[key];

              control.loading = true;
              preferences[key] = control.props.state = enabled;
            });

            m.redraw();

            user.save({ preferences: preferences }).then(function () {
              keys.forEach(function (key) {
                return _this4.inputs[key].loading = false;
              });

              m.redraw();
            });
          }
        }, {
          key: 'toggleMethod',
          value: function toggleMethod(method) {
            var _this5 = this;

            var keys = this.types.map(function (type) {
              return _this5.preferenceKey(type.name, method);
            }).filter(function (key) {
              return !_this5.inputs[key].props.disabled;
            });

            this.toggle(keys);
          }
        }, {
          key: 'toggleType',
          value: function toggleType(type) {
            var _this6 = this;

            var keys = this.methods.map(function (method) {
              return _this6.preferenceKey(type, method.name);
            }).filter(function (key) {
              return !_this6.inputs[key].props.disabled;
            });

            this.toggle(keys);
          }
        }, {
          key: 'preferenceKey',
          value: function preferenceKey(type, method) {
            return 'notify_' + type + '_' + method;
          }
        }, {
          key: 'notificationTypes',
          value: function notificationTypes() {
            var items = new ItemList();

            items.add('discussionRenamed', {
              name: 'discussionRenamed',
              icon: 'pencil',
              label: app.translator.trans('core.forum.settings.notify_discussion_renamed_label')
            });

            return items;
          }
        }]);
        return NotificationGrid;
      }(Component);

      _export('default', NotificationGrid);
    }
  };
});;
'use strict';

System.register('flarum/components/NotificationList', ['flarum/Component', 'flarum/helpers/listItems', 'flarum/components/Button', 'flarum/components/LoadingIndicator', 'flarum/models/Discussion'], function (_export, _context) {
  "use strict";

  var Component, listItems, Button, LoadingIndicator, Discussion, NotificationList;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumComponentsLoadingIndicator) {
      LoadingIndicator = _flarumComponentsLoadingIndicator.default;
    }, function (_flarumModelsDiscussion) {
      Discussion = _flarumModelsDiscussion.default;
    }],
    execute: function () {
      NotificationList = function (_Component) {
        babelHelpers.inherits(NotificationList, _Component);

        function NotificationList() {
          babelHelpers.classCallCheck(this, NotificationList);
          return babelHelpers.possibleConstructorReturn(this, (NotificationList.__proto__ || Object.getPrototypeOf(NotificationList)).apply(this, arguments));
        }

        babelHelpers.createClass(NotificationList, [{
          key: 'init',
          value: function init() {
            /**
             * Whether or not the notifications are loading.
             *
             * @type {Boolean}
             */
            this.loading = false;
          }
        }, {
          key: 'view',
          value: function view() {
            var groups = [];

            if (app.cache.notifications) {
              var discussions = {};

              // Build an array of discussions which the notifications are related to,
              // and add the notifications as children.
              app.cache.notifications.forEach(function (notification) {
                var subject = notification.subject();

                if (typeof subject === 'undefined') return;

                // Get the discussion that this notification is related to. If it's not
                // directly related to a discussion, it may be related to a post or
                // other entity which is related to a discussion.
                var discussion = false;
                if (subject instanceof Discussion) discussion = subject;else if (subject && subject.discussion) discussion = subject.discussion();

                // If the notification is not related to a discussion directly or
                // indirectly, then we will assign it to a neutral group.
                var key = discussion ? discussion.id() : 0;
                discussions[key] = discussions[key] || { discussion: discussion, notifications: [] };
                discussions[key].notifications.push(notification);

                if (groups.indexOf(discussions[key]) === -1) {
                  groups.push(discussions[key]);
                }
              });
            }

            return m(
              'div',
              { className: 'NotificationList' },
              m(
                'div',
                { className: 'NotificationList-header' },
                m(
                  'div',
                  { className: 'App-primaryControl' },
                  Button.component({
                    className: 'Button Button--icon Button--link',
                    icon: 'check',
                    title: app.translator.trans('core.forum.notifications.mark_all_as_read_tooltip'),
                    onclick: this.markAllAsRead.bind(this)
                  })
                ),
                m(
                  'h4',
                  { className: 'App-titleControl App-titleControl--text' },
                  app.translator.trans('core.forum.notifications.title')
                )
              ),
              m(
                'div',
                { className: 'NotificationList-content' },
                groups.length ? groups.map(function (group) {
                  var badges = group.discussion && group.discussion.badges().toArray();

                  return m(
                    'div',
                    { className: 'NotificationGroup' },
                    group.discussion ? m(
                      'a',
                      { className: 'NotificationGroup-header',
                        href: app.route.discussion(group.discussion),
                        config: m.route },
                      badges && badges.length ? m(
                        'ul',
                        { className: 'NotificationGroup-badges badges' },
                        listItems(badges)
                      ) : '',
                      group.discussion.title()
                    ) : m(
                      'div',
                      { className: 'NotificationGroup-header' },
                      app.forum.attribute('title')
                    ),
                    m(
                      'ul',
                      { className: 'NotificationGroup-content' },
                      group.notifications.map(function (notification) {
                        var NotificationComponent = app.notificationComponents[notification.contentType()];
                        return NotificationComponent ? m(
                          'li',
                          null,
                          NotificationComponent.component({ notification: notification })
                        ) : '';
                      })
                    )
                  );
                }) : !this.loading ? m(
                  'div',
                  { className: 'NotificationList-empty' },
                  app.translator.trans('core.forum.notifications.empty_text')
                ) : LoadingIndicator.component({ className: 'LoadingIndicator--block' })
              )
            );
          }
        }, {
          key: 'load',
          value: function load() {
            var _this2 = this;

            if (app.cache.notifications && !app.session.user.newNotificationsCount()) {
              return;
            }

            this.loading = true;
            m.redraw();

            app.store.find('notifications').then(function (notifications) {
              app.session.user.pushAttributes({ newNotificationsCount: 0 });
              app.cache.notifications = notifications.sort(function (a, b) {
                return b.time() - a.time();
              });
            }).catch(function () {}).then(function () {
              _this2.loading = false;
              m.redraw();
            });
          }
        }, {
          key: 'markAllAsRead',
          value: function markAllAsRead() {
            if (!app.cache.notifications) return;

            app.session.user.pushAttributes({ unreadNotificationsCount: 0 });

            app.cache.notifications.forEach(function (notification) {
              return notification.pushAttributes({ isRead: true });
            });

            app.request({
              url: app.forum.attribute('apiUrl') + '/notifications/read',
              method: 'POST'
            });
          }
        }]);
        return NotificationList;
      }(Component);

      _export('default', NotificationList);
    }
  };
});;
'use strict';

System.register('flarum/components/NotificationsDropdown', ['flarum/components/Dropdown', 'flarum/helpers/icon', 'flarum/components/NotificationList'], function (_export, _context) {
  "use strict";

  var Dropdown, icon, NotificationList, NotificationsDropdown;
  return {
    setters: [function (_flarumComponentsDropdown) {
      Dropdown = _flarumComponentsDropdown.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }, function (_flarumComponentsNotificationList) {
      NotificationList = _flarumComponentsNotificationList.default;
    }],
    execute: function () {
      NotificationsDropdown = function (_Dropdown) {
        babelHelpers.inherits(NotificationsDropdown, _Dropdown);

        function NotificationsDropdown() {
          babelHelpers.classCallCheck(this, NotificationsDropdown);
          return babelHelpers.possibleConstructorReturn(this, (NotificationsDropdown.__proto__ || Object.getPrototypeOf(NotificationsDropdown)).apply(this, arguments));
        }

        babelHelpers.createClass(NotificationsDropdown, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(NotificationsDropdown.prototype.__proto__ || Object.getPrototypeOf(NotificationsDropdown.prototype), 'init', this).call(this);

            this.list = new NotificationList();
          }
        }, {
          key: 'getButton',
          value: function getButton() {
            var newNotifications = this.getNewCount();
            var vdom = babelHelpers.get(NotificationsDropdown.prototype.__proto__ || Object.getPrototypeOf(NotificationsDropdown.prototype), 'getButton', this).call(this);

            vdom.attrs.title = this.props.label;

            vdom.attrs.className += newNotifications ? ' new' : '';
            vdom.attrs.onclick = this.onclick.bind(this);

            return vdom;
          }
        }, {
          key: 'getButtonContent',
          value: function getButtonContent() {
            var unread = this.getUnreadCount();

            return [icon(this.props.icon, { className: 'Button-icon' }), unread ? m(
              'span',
              { className: 'NotificationsDropdown-unread' },
              unread
            ) : '', m(
              'span',
              { className: 'Button-label' },
              this.props.label
            )];
          }
        }, {
          key: 'getMenu',
          value: function getMenu() {
            return m(
              'div',
              { className: 'Dropdown-menu ' + this.props.menuClassName, onclick: this.menuClick.bind(this) },
              this.showing ? this.list.render() : ''
            );
          }
        }, {
          key: 'onclick',
          value: function onclick() {
            if (app.drawer.isOpen()) {
              this.goToRoute();
            } else {
              this.list.load();
            }
          }
        }, {
          key: 'goToRoute',
          value: function goToRoute() {
            m.route(app.route('notifications'));
          }
        }, {
          key: 'getUnreadCount',
          value: function getUnreadCount() {
            return app.session.user.unreadNotificationsCount();
          }
        }, {
          key: 'getNewCount',
          value: function getNewCount() {
            return app.session.user.newNotificationsCount();
          }
        }, {
          key: 'menuClick',
          value: function menuClick(e) {
            // Don't close the notifications dropdown if the user is opening a link in a
            // new tab or window.
            if (e.shiftKey || e.metaKey || e.ctrlKey || e.which === 2) e.stopPropagation();
          }
        }], [{
          key: 'initProps',
          value: function initProps(props) {
            props.className = props.className || 'NotificationsDropdown';
            props.buttonClassName = props.buttonClassName || 'Button Button--flat';
            props.menuClassName = props.menuClassName || 'Dropdown-menu--right';
            props.label = props.label || app.translator.trans('core.forum.notifications.tooltip');
            props.icon = props.icon || 'bell';

            babelHelpers.get(NotificationsDropdown.__proto__ || Object.getPrototypeOf(NotificationsDropdown), 'initProps', this).call(this, props);
          }
        }]);
        return NotificationsDropdown;
      }(Dropdown);

      _export('default', NotificationsDropdown);
    }
  };
});;
'use strict';

System.register('flarum/components/NotificationsPage', ['flarum/components/Page', 'flarum/components/NotificationList'], function (_export, _context) {
  "use strict";

  var Page, NotificationList, NotificationsPage;
  return {
    setters: [function (_flarumComponentsPage) {
      Page = _flarumComponentsPage.default;
    }, function (_flarumComponentsNotificationList) {
      NotificationList = _flarumComponentsNotificationList.default;
    }],
    execute: function () {
      NotificationsPage = function (_Page) {
        babelHelpers.inherits(NotificationsPage, _Page);

        function NotificationsPage() {
          babelHelpers.classCallCheck(this, NotificationsPage);
          return babelHelpers.possibleConstructorReturn(this, (NotificationsPage.__proto__ || Object.getPrototypeOf(NotificationsPage)).apply(this, arguments));
        }

        babelHelpers.createClass(NotificationsPage, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(NotificationsPage.prototype.__proto__ || Object.getPrototypeOf(NotificationsPage.prototype), 'init', this).call(this);

            app.history.push('notifications');

            this.list = new NotificationList();
            this.list.load();

            this.bodyClass = 'App--notifications';
          }
        }, {
          key: 'view',
          value: function view() {
            return m(
              'div',
              { className: 'NotificationsPage' },
              this.list.render()
            );
          }
        }]);
        return NotificationsPage;
      }(Page);

      _export('default', NotificationsPage);
    }
  };
});;
'use strict';

System.register('flarum/components/Page', ['flarum/Component'], function (_export, _context) {
  "use strict";

  var Component, Page;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }],
    execute: function () {
      Page = function (_Component) {
        babelHelpers.inherits(Page, _Component);

        function Page() {
          babelHelpers.classCallCheck(this, Page);
          return babelHelpers.possibleConstructorReturn(this, (Page.__proto__ || Object.getPrototypeOf(Page)).apply(this, arguments));
        }

        babelHelpers.createClass(Page, [{
          key: 'init',
          value: function init() {
            app.previous = app.current;
            app.current = this;

            app.drawer.hide();
            app.modal.close();

            /**
             * A class name to apply to the body while the route is active.
             *
             * @type {String}
             */
            this.bodyClass = '';
          }
        }, {
          key: 'config',
          value: function config(isInitialized, context) {
            var _this2 = this;

            if (isInitialized) return;

            if (this.bodyClass) {
              $('#app').addClass(this.bodyClass);

              context.onunload = function () {
                return $('#app').removeClass(_this2.bodyClass);
              };
            }
          }
        }]);
        return Page;
      }(Component);

      _export('default', Page);
    }
  };
});;
"use strict";

System.register("flarum/components/Placeholder", ["flarum/Component"], function (_export, _context) {
  "use strict";

  var Component, Placeholder;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }],
    execute: function () {
      Placeholder = function (_Component) {
        babelHelpers.inherits(Placeholder, _Component);

        function Placeholder() {
          babelHelpers.classCallCheck(this, Placeholder);
          return babelHelpers.possibleConstructorReturn(this, (Placeholder.__proto__ || Object.getPrototypeOf(Placeholder)).apply(this, arguments));
        }

        babelHelpers.createClass(Placeholder, [{
          key: "view",
          value: function view() {
            return m(
              "div",
              { className: "Placeholder" },
              m(
                "p",
                null,
                this.props.text
              )
            );
          }
        }]);
        return Placeholder;
      }(Component);

      _export("default", Placeholder);
    }
  };
});;
'use strict';

System.register('flarum/components/Post', ['flarum/Component', 'flarum/utils/SubtreeRetainer', 'flarum/components/Dropdown', 'flarum/utils/PostControls', 'flarum/helpers/listItems', 'flarum/utils/ItemList'], function (_export, _context) {
  "use strict";

  var Component, SubtreeRetainer, Dropdown, PostControls, listItems, ItemList, Post;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumUtilsSubtreeRetainer) {
      SubtreeRetainer = _flarumUtilsSubtreeRetainer.default;
    }, function (_flarumComponentsDropdown) {
      Dropdown = _flarumComponentsDropdown.default;
    }, function (_flarumUtilsPostControls) {
      PostControls = _flarumUtilsPostControls.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }],
    execute: function () {
      Post = function (_Component) {
        babelHelpers.inherits(Post, _Component);

        function Post() {
          babelHelpers.classCallCheck(this, Post);
          return babelHelpers.possibleConstructorReturn(this, (Post.__proto__ || Object.getPrototypeOf(Post)).apply(this, arguments));
        }

        babelHelpers.createClass(Post, [{
          key: 'init',
          value: function init() {
            var _this2 = this;

            this.loading = false;

            /**
             * Set up a subtree retainer so that the post will not be redrawn
             * unless new data comes in.
             *
             * @type {SubtreeRetainer}
             */
            this.subtree = new SubtreeRetainer(function () {
              return _this2.props.post.freshness;
            }, function () {
              var user = _this2.props.post.user();
              return user && user.freshness;
            }, function () {
              return _this2.controlsOpen;
            });
          }
        }, {
          key: 'view',
          value: function view() {
            var _this3 = this;

            var attrs = this.attrs();

            attrs.className = 'Post ' + (this.loading ? 'Post--loading ' : '') + (attrs.className || '');

            return m(
              'article',
              attrs,
              this.subtree.retain() || function () {
                var controls = PostControls.controls(_this3.props.post, _this3).toArray();

                return m(
                  'div',
                  null,
                  _this3.content(),
                  m(
                    'aside',
                    { className: 'Post-actions' },
                    m(
                      'ul',
                      null,
                      listItems(_this3.actionItems().toArray()),
                      controls.length ? m(
                        'li',
                        null,
                        m(
                          Dropdown,
                          {
                            className: 'Post-controls',
                            buttonClassName: 'Button Button--icon Button--flat',
                            menuClassName: 'Dropdown-menu--right',
                            icon: 'ellipsis-h',
                            onshow: function onshow() {
                              return _this3.$('.Post-actions').addClass('open');
                            },
                            onhide: function onhide() {
                              return _this3.$('.Post-actions').removeClass('open');
                            } },
                          controls
                        )
                      ) : ''
                    )
                  ),
                  m(
                    'footer',
                    { className: 'Post-footer' },
                    m(
                      'ul',
                      null,
                      listItems(_this3.footerItems().toArray())
                    )
                  )
                );
              }()
            );
          }
        }, {
          key: 'config',
          value: function config(isInitialized) {
            var $actions = this.$('.Post-actions');
            var $controls = this.$('.Post-controls');

            $actions.toggleClass('open', $controls.hasClass('open'));
          }
        }, {
          key: 'attrs',
          value: function attrs() {
            return {};
          }
        }, {
          key: 'content',
          value: function content() {
            return [];
          }
        }, {
          key: 'actionItems',
          value: function actionItems() {
            return new ItemList();
          }
        }, {
          key: 'footerItems',
          value: function footerItems() {
            return new ItemList();
          }
        }]);
        return Post;
      }(Component);

      _export('default', Post);
    }
  };
});;
'use strict';

System.register('flarum/components/PostEdited', ['flarum/Component', 'flarum/utils/humanTime', 'flarum/utils/extractText'], function (_export, _context) {
  "use strict";

  var Component, humanTime, extractText, PostEdited;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumUtilsHumanTime) {
      humanTime = _flarumUtilsHumanTime.default;
    }, function (_flarumUtilsExtractText) {
      extractText = _flarumUtilsExtractText.default;
    }],
    execute: function () {
      PostEdited = function (_Component) {
        babelHelpers.inherits(PostEdited, _Component);

        function PostEdited() {
          babelHelpers.classCallCheck(this, PostEdited);
          return babelHelpers.possibleConstructorReturn(this, (PostEdited.__proto__ || Object.getPrototypeOf(PostEdited)).apply(this, arguments));
        }

        babelHelpers.createClass(PostEdited, [{
          key: 'init',
          value: function init() {
            this.shouldUpdateTooltip = false;
            this.oldEditedInfo = null;
          }
        }, {
          key: 'view',
          value: function view() {
            var post = this.props.post;
            var editUser = post.editUser();
            var editedInfo = extractText(app.translator.trans('core.forum.post.edited_tooltip', { user: editUser, ago: humanTime(post.editTime()) }));
            if (editedInfo !== this.oldEditedInfo) {
              this.shouldUpdateTooltip = true;
              this.oldEditedInfo = editedInfo;
            }

            return m(
              'span',
              { className: 'PostEdited', title: editedInfo },
              app.translator.trans('core.forum.post.edited_text')
            );
          }
        }, {
          key: 'config',
          value: function config(isInitialized) {
            if (this.shouldUpdateTooltip) {
              this.$().tooltip('destroy').tooltip();
              this.shouldUpdateTooltip = false;
            }
          }
        }]);
        return PostEdited;
      }(Component);

      _export('default', PostEdited);
    }
  };
});;
'use strict';

System.register('flarum/components/PostMeta', ['flarum/Component', 'flarum/helpers/humanTime', 'flarum/helpers/fullTime'], function (_export, _context) {
  "use strict";

  var Component, humanTime, fullTime, PostMeta;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumHelpersHumanTime) {
      humanTime = _flarumHelpersHumanTime.default;
    }, function (_flarumHelpersFullTime) {
      fullTime = _flarumHelpersFullTime.default;
    }],
    execute: function () {
      PostMeta = function (_Component) {
        babelHelpers.inherits(PostMeta, _Component);

        function PostMeta() {
          babelHelpers.classCallCheck(this, PostMeta);
          return babelHelpers.possibleConstructorReturn(this, (PostMeta.__proto__ || Object.getPrototypeOf(PostMeta)).apply(this, arguments));
        }

        babelHelpers.createClass(PostMeta, [{
          key: 'view',
          value: function view() {
            var post = this.props.post;
            var time = post.time();
            var permalink = this.getPermalink(post);
            var touch = 'ontouchstart' in document.documentElement;

            // When the dropdown menu is shown, select the contents of the permalink
            // input so that the user can quickly copy the URL.
            var selectPermalink = function selectPermalink() {
              var _this2 = this;

              setTimeout(function () {
                return $(_this2).parent().find('.PostMeta-permalink').select();
              });

              m.redraw.strategy('none');
            };

            return m(
              'div',
              { className: 'Dropdown PostMeta' },
              m(
                'a',
                { className: 'Dropdown-toggle', onclick: selectPermalink, 'data-toggle': 'dropdown' },
                humanTime(time)
              ),
              m(
                'div',
                { className: 'Dropdown-menu dropdown-menu' },
                m(
                  'span',
                  { className: 'PostMeta-number' },
                  app.translator.trans('core.forum.post.number_tooltip', { number: post.number() })
                ),
                ' ',
                m(
                  'span',
                  { className: 'PostMeta-time' },
                  fullTime(time)
                ),
                ' ',
                m(
                  'span',
                  { className: 'PostMeta-ip' },
                  post.data.attributes.ipAddress
                ),
                touch ? m(
                  'a',
                  { className: 'Button PostMeta-permalink', href: permalink },
                  permalink
                ) : m('input', { className: 'FormControl PostMeta-permalink', value: permalink, onclick: function onclick(e) {
                    return e.stopPropagation();
                  } })
              )
            );
          }
        }, {
          key: 'getPermalink',
          value: function getPermalink(post) {
            return window.location.origin + app.route.post(post);
          }
        }]);
        return PostMeta;
      }(Component);

      _export('default', PostMeta);
    }
  };
});;
'use strict';

System.register('flarum/components/PostPreview', ['flarum/Component', 'flarum/helpers/avatar', 'flarum/helpers/username', 'flarum/helpers/highlight'], function (_export, _context) {
  "use strict";

  var Component, avatar, username, highlight, PostPreview;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumHelpersAvatar) {
      avatar = _flarumHelpersAvatar.default;
    }, function (_flarumHelpersUsername) {
      username = _flarumHelpersUsername.default;
    }, function (_flarumHelpersHighlight) {
      highlight = _flarumHelpersHighlight.default;
    }],
    execute: function () {
      PostPreview = function (_Component) {
        babelHelpers.inherits(PostPreview, _Component);

        function PostPreview() {
          babelHelpers.classCallCheck(this, PostPreview);
          return babelHelpers.possibleConstructorReturn(this, (PostPreview.__proto__ || Object.getPrototypeOf(PostPreview)).apply(this, arguments));
        }

        babelHelpers.createClass(PostPreview, [{
          key: 'view',
          value: function view() {
            var post = this.props.post;
            var user = post.user();
            var excerpt = highlight(post.contentPlain(), this.props.highlight, 300);

            return m(
              'a',
              { className: 'PostPreview', href: app.route.post(post), config: m.route, onclick: this.props.onclick },
              m(
                'span',
                { className: 'PostPreview-content' },
                avatar(user),
                username(user),
                ' ',
                m(
                  'span',
                  { className: 'PostPreview-excerpt' },
                  excerpt
                )
              )
            );
          }
        }]);
        return PostPreview;
      }(Component);

      _export('default', PostPreview);
    }
  };
});;
'use strict';

System.register('flarum/components/PostStream', ['flarum/Component', 'flarum/utils/ScrollListener', 'flarum/components/LoadingPost', 'flarum/utils/anchorScroll', 'flarum/utils/mixin', 'flarum/utils/evented', 'flarum/components/ReplyPlaceholder', 'flarum/components/Button'], function (_export, _context) {
  "use strict";

  var Component, ScrollListener, PostLoading, anchorScroll, mixin, evented, ReplyPlaceholder, Button, PostStream;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumUtilsScrollListener) {
      ScrollListener = _flarumUtilsScrollListener.default;
    }, function (_flarumComponentsLoadingPost) {
      PostLoading = _flarumComponentsLoadingPost.default;
    }, function (_flarumUtilsAnchorScroll) {
      anchorScroll = _flarumUtilsAnchorScroll.default;
    }, function (_flarumUtilsMixin) {
      mixin = _flarumUtilsMixin.default;
    }, function (_flarumUtilsEvented) {
      evented = _flarumUtilsEvented.default;
    }, function (_flarumComponentsReplyPlaceholder) {
      ReplyPlaceholder = _flarumComponentsReplyPlaceholder.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }],
    execute: function () {
      PostStream = function (_Component) {
        babelHelpers.inherits(PostStream, _Component);

        function PostStream() {
          babelHelpers.classCallCheck(this, PostStream);
          return babelHelpers.possibleConstructorReturn(this, (PostStream.__proto__ || Object.getPrototypeOf(PostStream)).apply(this, arguments));
        }

        babelHelpers.createClass(PostStream, [{
          key: 'init',
          value: function init() {
            /**
             * The discussion to display the post stream for.
             *
             * @type {Discussion}
             */
            this.discussion = this.props.discussion;

            /**
             * Whether or not the infinite-scrolling auto-load functionality is
             * disabled.
             *
             * @type {Boolean}
             */
            this.paused = false;

            this.scrollListener = new ScrollListener(this.onscroll.bind(this));
            this.loadPageTimeouts = {};
            this.pagesLoading = 0;

            this.show(this.props.includedPosts);
          }
        }, {
          key: 'goToNumber',
          value: function goToNumber(number, noAnimation) {
            var _this2 = this;

            // If we want to go to the reply preview, then we will go to the end of the
            // discussion and then scroll to the very bottom of the page.
            if (number === 'reply') {
              return this.goToLast().then(function () {
                $('html,body').stop(true).animate({
                  scrollTop: $(document).height() - $(window).height()
                }, 'fast', function () {
                  _this2.flashItem(_this2.$('.PostStream-item:last-child'));
                });
              });
            }

            this.paused = true;

            var promise = this.loadNearNumber(number);

            m.redraw(true);

            return promise.then(function () {
              m.redraw(true);

              _this2.scrollToNumber(number, noAnimation).done(_this2.unpause.bind(_this2));
            });
          }
        }, {
          key: 'goToIndex',
          value: function goToIndex(index, backwards, noAnimation) {
            var _this3 = this;

            this.paused = true;

            var promise = this.loadNearIndex(index);

            m.redraw(true);

            return promise.then(function () {
              anchorScroll(_this3.$('.PostStream-item:' + (backwards ? 'last' : 'first')), function () {
                return m.redraw(true);
              });

              _this3.scrollToIndex(index, noAnimation, backwards).done(_this3.unpause.bind(_this3));
            });
          }
        }, {
          key: 'goToFirst',
          value: function goToFirst() {
            return this.goToIndex(0);
          }
        }, {
          key: 'goToLast',
          value: function goToLast() {
            return this.goToIndex(this.count() - 1, true);
          }
        }, {
          key: 'update',
          value: function update() {
            if (!this.viewingEnd) return;

            this.visibleEnd = this.count();

            this.loadRange(this.visibleStart, this.visibleEnd).then(function () {
              return m.redraw();
            });
          }
        }, {
          key: 'count',
          value: function count() {
            return this.discussion.postIds().length;
          }
        }, {
          key: 'sanitizeIndex',
          value: function sanitizeIndex(index) {
            return Math.max(0, Math.min(this.count(), index));
          }
        }, {
          key: 'show',
          value: function show(posts) {
            this.visibleStart = posts.length ? this.discussion.postIds().indexOf(posts[0].id()) : 0;
            this.visibleEnd = this.visibleStart + posts.length;
          }
        }, {
          key: 'reset',
          value: function reset(start, end) {
            this.visibleStart = start || 0;
            this.visibleEnd = this.sanitizeIndex(end || this.constructor.loadCount);
          }
        }, {
          key: 'posts',
          value: function posts() {
            return this.discussion.postIds().slice(this.visibleStart, this.visibleEnd).map(function (id) {
              var post = app.store.getById('posts', id);

              return post && post.discussion() && typeof post.canEdit() !== 'undefined' ? post : null;
            });
          }
        }, {
          key: 'view',
          value: function view() {
            var _this4 = this;

            function fadeIn(element, isInitialized, context) {
              if (!context.fadedIn) $(element).hide().fadeIn();
              context.fadedIn = true;
            }

            var lastTime = void 0;

            this.visibleEnd = this.sanitizeIndex(this.visibleEnd);
            this.viewingEnd = this.visibleEnd === this.count();

            var posts = this.posts();
            var postIds = this.discussion.postIds();

            var items = posts.map(function (post, i) {
              var content = void 0;
              var attrs = { 'data-index': _this4.visibleStart + i };

              if (post) {
                var time = post.time();
                var PostComponent = app.postComponents[post.contentType()];
                content = PostComponent ? PostComponent.component({ post: post }) : '';

                attrs.key = 'post' + post.id();
                attrs.config = fadeIn;
                attrs['data-time'] = time.toISOString();
                attrs['data-number'] = post.number();
                attrs['data-id'] = post.id();
                attrs['data-type'] = post.contentType();

                // If the post before this one was more than 4 hours ago, we will
                // display a 'time gap' indicating how long it has been in between
                // the posts.
                var dt = time - lastTime;

                if (dt > 1000 * 60 * 60 * 24 * 4) {
                  content = [m(
                    'div',
                    { className: 'PostStream-timeGap' },
                    m(
                      'span',
                      null,
                      app.translator.trans('core.forum.post_stream.time_lapsed_text', { period: moment.duration(dt).humanize() })
                    )
                  ), content];
                }

                lastTime = time;
              } else {
                attrs.key = 'post' + postIds[_this4.visibleStart + i];

                content = PostLoading.component();
              }

              return m(
                'div',
                babelHelpers.extends({ className: 'PostStream-item' }, attrs),
                content
              );
            });

            if (!this.viewingEnd && posts[this.visibleEnd - this.visibleStart - 1]) {
              items.push(m(
                'div',
                { className: 'PostStream-loadMore', key: 'loadMore' },
                m(
                  Button,
                  { className: 'Button', onclick: this.loadNext.bind(this) },
                  app.translator.trans('core.forum.post_stream.load_more_button')
                )
              ));
            }

            // If we're viewing the end of the discussion, the user can reply, and
            // is not already doing so, then show a 'write a reply' placeholder.
            if (this.viewingEnd && (!app.session.user || this.discussion.canReply())) {
              items.push(m(
                'div',
                { className: 'PostStream-item', key: 'reply' },
                ReplyPlaceholder.component({ discussion: this.discussion })
              ));
            }

            return m(
              'div',
              { className: 'PostStream' },
              items
            );
          }
        }, {
          key: 'config',
          value: function config(isInitialized, context) {
            var _this5 = this;

            if (isInitialized) return;

            // This is wrapped in setTimeout due to the following Mithril issue:
            // https://github.com/lhorie/mithril.js/issues/637
            setTimeout(function () {
              return _this5.scrollListener.start();
            });

            context.onunload = function () {
              _this5.scrollListener.stop();
              clearTimeout(_this5.calculatePositionTimeout);
            };
          }
        }, {
          key: 'onscroll',
          value: function onscroll(top) {
            if (this.paused) return;

            var marginTop = this.getMarginTop();
            var viewportHeight = $(window).height() - marginTop;
            var viewportTop = top + marginTop;
            var loadAheadDistance = 300;

            if (this.visibleStart > 0) {
              var $item = this.$('.PostStream-item[data-index=' + this.visibleStart + ']');

              if ($item.length && $item.offset().top > viewportTop - loadAheadDistance) {
                this.loadPrevious();
              }
            }

            if (this.visibleEnd < this.count()) {
              var _$item = this.$('.PostStream-item[data-index=' + (this.visibleEnd - 1) + ']');

              if (_$item.length && _$item.offset().top + _$item.outerHeight(true) < viewportTop + viewportHeight + loadAheadDistance) {
                this.loadNext();
              }
            }

            // Throttle calculation of our position (start/end numbers of posts in the
            // viewport) to 100ms.
            clearTimeout(this.calculatePositionTimeout);
            this.calculatePositionTimeout = setTimeout(this.calculatePosition.bind(this), 100);
          }
        }, {
          key: 'loadNext',
          value: function loadNext() {
            var start = this.visibleEnd;
            var end = this.visibleEnd = this.sanitizeIndex(this.visibleEnd + this.constructor.loadCount);

            // Unload the posts which are two pages back from the page we're currently
            // loading.
            var twoPagesAway = start - this.constructor.loadCount * 2;
            if (twoPagesAway > this.visibleStart && twoPagesAway >= 0) {
              this.visibleStart = twoPagesAway + this.constructor.loadCount + 1;

              if (this.loadPageTimeouts[twoPagesAway]) {
                clearTimeout(this.loadPageTimeouts[twoPagesAway]);
                this.loadPageTimeouts[twoPagesAway] = null;
                this.pagesLoading--;
              }
            }

            this.loadPage(start, end);
          }
        }, {
          key: 'loadPrevious',
          value: function loadPrevious() {
            var end = this.visibleStart;
            var start = this.visibleStart = this.sanitizeIndex(this.visibleStart - this.constructor.loadCount);

            // Unload the posts which are two pages back from the page we're currently
            // loading.
            var twoPagesAway = start + this.constructor.loadCount * 2;
            if (twoPagesAway < this.visibleEnd && twoPagesAway <= this.count()) {
              this.visibleEnd = twoPagesAway;

              if (this.loadPageTimeouts[twoPagesAway]) {
                clearTimeout(this.loadPageTimeouts[twoPagesAway]);
                this.loadPageTimeouts[twoPagesAway] = null;
                this.pagesLoading--;
              }
            }

            this.loadPage(start, end, true);
          }
        }, {
          key: 'loadPage',
          value: function loadPage(start, end, backwards) {
            var _this6 = this;

            var redraw = function redraw() {
              if (start < _this6.visibleStart || end > _this6.visibleEnd) return;

              var anchorIndex = backwards ? _this6.visibleEnd - 1 : _this6.visibleStart;
              anchorScroll('.PostStream-item[data-index="' + anchorIndex + '"]', function () {
                return m.redraw(true);
              });

              _this6.unpause();
            };
            redraw();

            this.loadPageTimeouts[start] = setTimeout(function () {
              _this6.loadRange(start, end).then(function () {
                redraw();
                _this6.pagesLoading--;
              });
              _this6.loadPageTimeouts[start] = null;
            }, this.pagesLoading ? 1000 : 0);

            this.pagesLoading++;
          }
        }, {
          key: 'loadRange',
          value: function loadRange(start, end) {
            var loadIds = [];
            var loaded = [];

            this.discussion.postIds().slice(start, end).forEach(function (id) {
              var post = app.store.getById('posts', id);

              if (post && post.discussion() && typeof post.canEdit() !== 'undefined') {
                loaded.push(post);
              } else {
                loadIds.push(id);
              }
            });

            return loadIds.length ? app.store.find('posts', loadIds) : m.deferred().resolve(loaded).promise;
          }
        }, {
          key: 'loadNearNumber',
          value: function loadNearNumber(number) {
            if (this.posts().some(function (post) {
              return post && Number(post.number()) === Number(number);
            })) {
              return m.deferred().resolve().promise;
            }

            this.reset();

            return app.store.find('posts', {
              filter: { discussion: this.discussion.id() },
              page: { near: number }
            }).then(this.show.bind(this));
          }
        }, {
          key: 'loadNearIndex',
          value: function loadNearIndex(index) {
            if (index >= this.visibleStart && index <= this.visibleEnd) {
              return m.deferred().resolve().promise;
            }

            var start = this.sanitizeIndex(index - this.constructor.loadCount / 2);
            var end = start + this.constructor.loadCount;

            this.reset(start, end);

            return this.loadRange(start, end).then(this.show.bind(this));
          }
        }, {
          key: 'calculatePosition',
          value: function calculatePosition() {
            var marginTop = this.getMarginTop();
            var $window = $(window);
            var viewportHeight = $window.height() - marginTop;
            var scrollTop = $window.scrollTop() + marginTop;
            var startNumber = void 0;
            var endNumber = void 0;

            this.$('.PostStream-item').each(function () {
              var $item = $(this);
              var top = $item.offset().top;
              var height = $item.outerHeight(true);

              if (top + height > scrollTop) {
                if (!startNumber) {
                  startNumber = endNumber = $item.data('number');
                }

                if (top + height < scrollTop + viewportHeight) {
                  if ($item.data('number')) {
                    endNumber = $item.data('number');
                  }
                } else return false;
              }
            });

            if (startNumber) {
              this.trigger('positionChanged', startNumber || 1, endNumber);
            }
          }
        }, {
          key: 'getMarginTop',
          value: function getMarginTop() {
            return this.$() && $('#header').outerHeight() + parseInt(this.$().css('margin-top'), 10);
          }
        }, {
          key: 'scrollToNumber',
          value: function scrollToNumber(number, noAnimation) {
            var $item = this.$('.PostStream-item[data-number=' + number + ']');

            return this.scrollToItem($item, noAnimation).done(this.flashItem.bind(this, $item));
          }
        }, {
          key: 'scrollToIndex',
          value: function scrollToIndex(index, noAnimation, bottom) {
            var $item = this.$('.PostStream-item[data-index=' + index + ']');

            return this.scrollToItem($item, noAnimation, true, bottom);
          }
        }, {
          key: 'scrollToItem',
          value: function scrollToItem($item, noAnimation, force, bottom) {
            var $container = $('html, body').stop(true);

            if ($item.length) {
              var itemTop = $item.offset().top - this.getMarginTop();
              var itemBottom = $item.offset().top + $item.height();
              var scrollTop = $(document).scrollTop();
              var scrollBottom = scrollTop + $(window).height();

              // If the item is already in the viewport, we may not need to scroll.
              // If we're scrolling to the bottom of an item, then we'll make sure the
              // bottom will line up with the top of the composer.
              if (force || itemTop < scrollTop || itemBottom > scrollBottom) {
                var top = bottom ? itemBottom - $(window).height() + app.composer.computedHeight() : $item.is(':first-child') ? 0 : itemTop;

                if (noAnimation) {
                  $container.scrollTop(top);
                } else if (top !== scrollTop) {
                  $container.animate({ scrollTop: top }, 'fast');
                }
              }
            }

            return $container.promise();
          }
        }, {
          key: 'flashItem',
          value: function flashItem($item) {
            $item.addClass('flash').one('animationend webkitAnimationEnd', function () {
              return $item.removeClass('flash');
            });
          }
        }, {
          key: 'unpause',
          value: function unpause() {
            this.paused = false;
            this.scrollListener.update(true);
            this.trigger('unpaused');
          }
        }]);
        return PostStream;
      }(Component);

      /**
       * The number of posts to load per page.
       *
       * @type {Integer}
       */
      PostStream.loadCount = 20;

      babelHelpers.extends(PostStream.prototype, evented);

      _export('default', PostStream);
    }
  };
});;
'use strict';

System.register('flarum/components/PostStreamScrubber', ['flarum/Component', 'flarum/helpers/icon', 'flarum/utils/ScrollListener', 'flarum/utils/SubtreeRetainer', 'flarum/utils/computed', 'flarum/utils/formatNumber'], function (_export, _context) {
  "use strict";

  var Component, icon, ScrollListener, SubtreeRetainer, computed, formatNumber, PostStreamScrubber;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }, function (_flarumUtilsScrollListener) {
      ScrollListener = _flarumUtilsScrollListener.default;
    }, function (_flarumUtilsSubtreeRetainer) {
      SubtreeRetainer = _flarumUtilsSubtreeRetainer.default;
    }, function (_flarumUtilsComputed) {
      computed = _flarumUtilsComputed.default;
    }, function (_flarumUtilsFormatNumber) {
      formatNumber = _flarumUtilsFormatNumber.default;
    }],
    execute: function () {
      PostStreamScrubber = function (_Component) {
        babelHelpers.inherits(PostStreamScrubber, _Component);

        function PostStreamScrubber() {
          babelHelpers.classCallCheck(this, PostStreamScrubber);
          return babelHelpers.possibleConstructorReturn(this, (PostStreamScrubber.__proto__ || Object.getPrototypeOf(PostStreamScrubber)).apply(this, arguments));
        }

        babelHelpers.createClass(PostStreamScrubber, [{
          key: 'init',
          value: function init() {
            this.handlers = {};

            /**
             * The index of the post that is currently at the top of the viewport.
             *
             * @type {Number}
             */
            this.index = 0;

            /**
             * The number of posts that are currently visible in the viewport.
             *
             * @type {Number}
             */
            this.visible = 1;

            /**
             * The description to render on the scrubber.
             *
             * @type {String}
             */
            this.description = '';

            // When the post stream begins loading posts at a certain index, we want our
            // scrubber scrollbar to jump to that position.
            this.props.stream.on('unpaused', this.handlers.streamWasUnpaused = this.streamWasUnpaused.bind(this));

            // Define a handler to update the state of the scrollbar to reflect the
            // current scroll position of the page.
            this.scrollListener = new ScrollListener(this.onscroll.bind(this));

            // Create a subtree retainer that will always cache the subtree after the
            // initial draw. We render parts of the scrubber using this because we
            // modify their DOM directly, and do not want Mithril messing around with
            // our changes.
            this.subtree = new SubtreeRetainer(function () {
              return true;
            });
          }
        }, {
          key: 'view',
          value: function view() {
            var retain = this.subtree.retain();
            var count = this.count();
            var unreadCount = this.props.stream.discussion.unreadCount();
            var unreadPercent = count ? Math.min(count - this.index, unreadCount) / count : 0;

            var viewing = app.translator.transChoice('core.forum.post_scrubber.viewing_text', count, {
              index: m(
                'span',
                { className: 'Scrubber-index' },
                retain || formatNumber(Math.min(Math.ceil(this.index + this.visible), count))
              ),
              count: m(
                'span',
                { className: 'Scrubber-count' },
                formatNumber(count)
              )
            });

            function styleUnread(element, isInitialized, context) {
              var $element = $(element);
              var newStyle = {
                top: 100 - unreadPercent * 100 + '%',
                height: unreadPercent * 100 + '%'
              };

              if (context.oldStyle) {
                $element.stop(true).css(context.oldStyle).animate(newStyle);
              } else {
                $element.css(newStyle);
              }

              context.oldStyle = newStyle;
            }

            return m(
              'div',
              { className: 'PostStreamScrubber Dropdown ' + (this.disabled() ? 'disabled ' : '') + (this.props.className || '') },
              m(
                'button',
                { className: 'Button Dropdown-toggle', 'data-toggle': 'dropdown' },
                viewing,
                ' ',
                icon('sort')
              ),
              m(
                'div',
                { className: 'Dropdown-menu dropdown-menu' },
                m(
                  'div',
                  { className: 'Scrubber' },
                  m(
                    'a',
                    { className: 'Scrubber-first', onclick: this.goToFirst.bind(this) },
                    icon('angle-double-up'),
                    ' ',
                    app.translator.trans('core.forum.post_scrubber.original_post_link')
                  ),
                  m(
                    'div',
                    { className: 'Scrubber-scrollbar' },
                    m('div', { className: 'Scrubber-before' }),
                    m(
                      'div',
                      { className: 'Scrubber-handle' },
                      m('div', { className: 'Scrubber-bar' }),
                      m(
                        'div',
                        { className: 'Scrubber-info' },
                        m(
                          'strong',
                          null,
                          viewing
                        ),
                        m(
                          'span',
                          { 'class': 'Scrubber-description' },
                          retain || this.description
                        )
                      )
                    ),
                    m('div', { className: 'Scrubber-after' }),
                    m(
                      'div',
                      { className: 'Scrubber-unread', config: styleUnread },
                      app.translator.trans('core.forum.post_scrubber.unread_text', { count: unreadCount })
                    )
                  ),
                  m(
                    'a',
                    { className: 'Scrubber-last', onclick: this.goToLast.bind(this) },
                    icon('angle-double-down'),
                    ' ',
                    app.translator.trans('core.forum.post_scrubber.now_link')
                  )
                )
              )
            );
          }
        }, {
          key: 'goToFirst',
          value: function goToFirst() {
            this.props.stream.goToFirst();
            this.index = 0;
            this.renderScrollbar(true);
          }
        }, {
          key: 'goToLast',
          value: function goToLast() {
            this.props.stream.goToLast();
            this.index = this.props.stream.count();
            this.renderScrollbar(true);
          }
        }, {
          key: 'count',
          value: function count() {
            return this.props.stream.count();
          }
        }, {
          key: 'streamWasUnpaused',
          value: function streamWasUnpaused() {
            this.update(window.pageYOffset);
            this.renderScrollbar(true);
          }
        }, {
          key: 'disabled',
          value: function disabled() {
            return this.visible >= this.count();
          }
        }, {
          key: 'onscroll',
          value: function onscroll(top) {
            var stream = this.props.stream;

            if (stream.paused || !stream.$()) return;

            this.update(top);
            this.renderScrollbar();
          }
        }, {
          key: 'update',
          value: function update(scrollTop) {
            var stream = this.props.stream;

            var marginTop = stream.getMarginTop();
            var viewportTop = scrollTop + marginTop;
            var viewportHeight = $(window).height() - marginTop;
            var viewportBottom = viewportTop + viewportHeight;

            // Before looping through all of the posts, we reset the scrollbar
            // properties to a 'default' state. These values reflect what would be
            // seen if the browser were scrolled right up to the top of the page,
            // and the viewport had a height of 0.
            var $items = stream.$('> .PostStream-item[data-index]');
            var index = $items.first().data('index') || 0;
            var visible = 0;
            var period = '';

            // Now loop through each of the items in the discussion. An 'item' is
            // either a single post or a 'gap' of one or more posts that haven't
            // been loaded yet.
            $items.each(function () {
              var $this = $(this);
              var top = $this.offset().top;
              var height = $this.outerHeight(true);

              // If this item is above the top of the viewport, skip to the next
              // one. If it's below the bottom of the viewport, break out of the
              // loop.
              if (top + height < viewportTop) {
                return true;
              }
              if (top > viewportTop + viewportHeight) {
                return false;
              }

              // Work out how many pixels of this item are visible inside the viewport.
              // Then add the proportion of this item's total height to the index.
              var visibleTop = Math.max(0, viewportTop - top);
              var visibleBottom = Math.min(height, viewportTop + viewportHeight - top);
              var visiblePost = visibleBottom - visibleTop;

              if (top <= viewportTop) {
                index = parseFloat($this.data('index')) + visibleTop / height;
              }

              if (visiblePost > 0) {
                visible += visiblePost / height;
              }

              // If this item has a time associated with it, then set the
              // scrollbar's current period to a formatted version of this time.
              var time = $this.data('time');
              if (time) period = time;
            });

            this.index = index;
            this.visible = visible;
            this.description = period ? moment(period).format('MMMM YYYY') : '';
          }
        }, {
          key: 'config',
          value: function config(isInitialized, context) {
            if (isInitialized) return;

            context.onunload = this.ondestroy.bind(this);

            this.scrollListener.start();

            // Whenever the window is resized, adjust the height of the scrollbar
            // so that it fills the height of the sidebar.
            $(window).on('resize', this.handlers.onresize = this.onresize.bind(this)).resize();

            // When any part of the whole scrollbar is clicked, we want to jump to
            // that position.
            this.$('.Scrubber-scrollbar').bind('click', this.onclick.bind(this))

            // Now we want to make the scrollbar handle draggable. Let's start by
            // preventing default browser events from messing things up.
            .css({ cursor: 'pointer', 'user-select': 'none' }).bind('dragstart mousedown touchstart', function (e) {
              return e.preventDefault();
            });

            // When the mouse is pressed on the scrollbar handle, we capture some
            // information about its current position. We will store this
            // information in an object and pass it on to the document's
            // mousemove/mouseup events later.
            this.dragging = false;
            this.mouseStart = 0;
            this.indexStart = 0;

            this.$('.Scrubber-handle').css('cursor', 'move').bind('mousedown touchstart', this.onmousedown.bind(this))

            // Exempt the scrollbar handle from the 'jump to' click event.
            .click(function (e) {
              return e.stopPropagation();
            });

            // When the mouse moves and when it is released, we pass the
            // information that we captured when the mouse was first pressed onto
            // some event handlers. These handlers will move the scrollbar/stream-
            // content as appropriate.
            $(document).on('mousemove touchmove', this.handlers.onmousemove = this.onmousemove.bind(this)).on('mouseup touchend', this.handlers.onmouseup = this.onmouseup.bind(this));
          }
        }, {
          key: 'ondestroy',
          value: function ondestroy() {
            this.scrollListener.stop();

            this.props.stream.off('unpaused', this.handlers.streamWasUnpaused);

            $(window).off('resize', this.handlers.onresize);

            $(document).off('mousemove touchmove', this.handlers.onmousemove).off('mouseup touchend', this.handlers.onmouseup);
          }
        }, {
          key: 'renderScrollbar',
          value: function renderScrollbar(animate) {
            var percentPerPost = this.percentPerPost();
            var index = this.index;
            var count = this.count();
            var visible = this.visible || 1;

            var $scrubber = this.$();
            $scrubber.find('.Scrubber-index').text(formatNumber(Math.ceil(index + visible)));
            $scrubber.find('.Scrubber-description').text(this.description);
            $scrubber.toggleClass('disabled', this.disabled());

            var heights = {};
            heights.before = Math.max(0, percentPerPost.index * Math.min(index, count - visible));
            heights.handle = Math.min(100 - heights.before, percentPerPost.visible * visible);
            heights.after = 100 - heights.before - heights.handle;

            var func = animate ? 'animate' : 'css';
            for (var part in heights) {
              var $part = $scrubber.find('.Scrubber-' + part);
              $part.stop(true, true)[func]({ height: heights[part] + '%' }, 'fast');

              // jQuery likes to put overflow:hidden, but because the scrollbar handle
              // has a negative margin-left, we need to override.
              if (func === 'animate') $part.css('overflow', 'visible');
            }
          }
        }, {
          key: 'percentPerPost',
          value: function percentPerPost() {
            var count = this.count() || 1;
            var visible = this.visible || 1;

            // To stop the handle of the scrollbar from getting too small when there
            // are many posts, we define a minimum percentage height for the handle
            // calculated from a 50 pixel limit. From this, we can calculate the
            // minimum percentage per visible post. If this is greater than the actual
            // percentage per post, then we need to adjust the 'before' percentage to
            // account for it.
            var minPercentVisible = 50 / this.$('.Scrubber-scrollbar').outerHeight() * 100;
            var percentPerVisiblePost = Math.max(100 / count, minPercentVisible / visible);
            var percentPerPost = count === visible ? 0 : (100 - percentPerVisiblePost * visible) / (count - visible);

            return {
              index: percentPerPost,
              visible: percentPerVisiblePost
            };
          }
        }, {
          key: 'onresize',
          value: function onresize() {
            this.scrollListener.update(true);

            // Adjust the height of the scrollbar so that it fills the height of
            // the sidebar and doesn't overlap the footer.
            var scrubber = this.$();
            var scrollbar = this.$('.Scrubber-scrollbar');

            scrollbar.css('max-height', $(window).height() - scrubber.offset().top + $(window).scrollTop() - parseInt($('#app').css('padding-bottom'), 10) - (scrubber.outerHeight() - scrollbar.outerHeight()));
          }
        }, {
          key: 'onmousedown',
          value: function onmousedown(e) {
            this.mouseStart = e.clientY || e.originalEvent.touches[0].clientY;
            this.indexStart = this.index;
            this.dragging = true;
            this.props.stream.paused = true;
            $('body').css('cursor', 'move');
          }
        }, {
          key: 'onmousemove',
          value: function onmousemove(e) {
            if (!this.dragging) return;

            // Work out how much the mouse has moved by - first in pixels, then
            // convert it to a percentage of the scrollbar's height, and then
            // finally convert it into an index. Add this delta index onto
            // the index at which the drag was started, and then scroll there.
            var deltaPixels = (e.clientY || e.originalEvent.touches[0].clientY) - this.mouseStart;
            var deltaPercent = deltaPixels / this.$('.Scrubber-scrollbar').outerHeight() * 100;
            var deltaIndex = deltaPercent / this.percentPerPost().index || 0;
            var newIndex = Math.min(this.indexStart + deltaIndex, this.count() - 1);

            this.index = Math.max(0, newIndex);
            this.renderScrollbar();
          }
        }, {
          key: 'onmouseup',
          value: function onmouseup() {
            if (!this.dragging) return;

            this.mouseStart = 0;
            this.indexStart = 0;
            this.dragging = false;
            $('body').css('cursor', '');

            this.$().removeClass('open');

            // If the index we've landed on is in a gap, then tell the stream-
            // content that we want to load those posts.
            var intIndex = Math.floor(this.index);
            this.props.stream.goToIndex(intIndex);
            this.renderScrollbar(true);
          }
        }, {
          key: 'onclick',
          value: function onclick(e) {
            // Calculate the index which we want to jump to based on the click position.

            // 1. Get the offset of the click from the top of the scrollbar, as a
            //    percentage of the scrollbar's height.
            var $scrollbar = this.$('.Scrubber-scrollbar');
            var offsetPixels = (e.clientY || e.originalEvent.touches[0].clientY) - $scrollbar.offset().top + $('body').scrollTop();
            var offsetPercent = offsetPixels / $scrollbar.outerHeight() * 100;

            // 2. We want the handle of the scrollbar to end up centered on the click
            //    position. Thus, we calculate the height of the handle in percent and
            //    use that to find a new offset percentage.
            offsetPercent = offsetPercent - parseFloat($scrollbar.find('.Scrubber-handle')[0].style.height) / 2;

            // 3. Now we can convert the percentage into an index, and tell the stream-
            //    content component to jump to that index.
            var offsetIndex = offsetPercent / this.percentPerPost().index;
            offsetIndex = Math.max(0, Math.min(this.count() - 1, offsetIndex));
            this.props.stream.goToIndex(Math.floor(offsetIndex));
            this.index = offsetIndex;
            this.renderScrollbar(true);

            this.$().removeClass('open');
          }
        }]);
        return PostStreamScrubber;
      }(Component);

      _export('default', PostStreamScrubber);
    }
  };
});;
'use strict';

System.register('flarum/components/PostsUserPage', ['flarum/components/UserPage', 'flarum/components/LoadingIndicator', 'flarum/components/Button', 'flarum/components/CommentPost'], function (_export, _context) {
  "use strict";

  var UserPage, LoadingIndicator, Button, CommentPost, PostsUserPage;
  return {
    setters: [function (_flarumComponentsUserPage) {
      UserPage = _flarumComponentsUserPage.default;
    }, function (_flarumComponentsLoadingIndicator) {
      LoadingIndicator = _flarumComponentsLoadingIndicator.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumComponentsCommentPost) {
      CommentPost = _flarumComponentsCommentPost.default;
    }],
    execute: function () {
      PostsUserPage = function (_UserPage) {
        babelHelpers.inherits(PostsUserPage, _UserPage);

        function PostsUserPage() {
          babelHelpers.classCallCheck(this, PostsUserPage);
          return babelHelpers.possibleConstructorReturn(this, (PostsUserPage.__proto__ || Object.getPrototypeOf(PostsUserPage)).apply(this, arguments));
        }

        babelHelpers.createClass(PostsUserPage, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(PostsUserPage.prototype.__proto__ || Object.getPrototypeOf(PostsUserPage.prototype), 'init', this).call(this);

            /**
             * Whether or not the activity feed is currently loading.
             *
             * @type {Boolean}
             */
            this.loading = true;

            /**
             * Whether or not there are any more activity items that can be loaded.
             *
             * @type {Boolean}
             */
            this.moreResults = false;

            /**
             * The Post models in the feed.
             *
             * @type {Post[]}
             */
            this.posts = [];

            /**
             * The number of activity items to load per request.
             *
             * @type {Integer}
             */
            this.loadLimit = 20;

            this.loadUser(m.route.param('username'));
          }
        }, {
          key: 'content',
          value: function content() {
            var footer = void 0;

            if (this.loading) {
              footer = LoadingIndicator.component();
            } else if (this.moreResults) {
              footer = m(
                'div',
                { className: 'PostsUserPage-loadMore' },
                Button.component({
                  children: app.translator.trans('core.forum.user.posts_load_more_button'),
                  className: 'Button',
                  onclick: this.loadMore.bind(this)
                })
              );
            }

            return m(
              'div',
              { className: 'PostsUserPage' },
              m(
                'ul',
                { className: 'PostsUserPage-list' },
                this.posts.map(function (post) {
                  return m(
                    'li',
                    null,
                    m(
                      'div',
                      { className: 'PostsUserPage-discussion' },
                      app.translator.trans('core.forum.user.in_discussion_text', { discussion: m(
                          'a',
                          { href: app.route.post(post), config: m.route },
                          post.discussion().title()
                        ) })
                    ),
                    CommentPost.component({ post: post })
                  );
                })
              ),
              footer
            );
          }
        }, {
          key: 'show',
          value: function show(user) {
            babelHelpers.get(PostsUserPage.prototype.__proto__ || Object.getPrototypeOf(PostsUserPage.prototype), 'show', this).call(this, user);

            this.refresh();
          }
        }, {
          key: 'refresh',
          value: function refresh() {
            this.loading = true;
            this.posts = [];

            m.lazyRedraw();

            this.loadResults().then(this.parseResults.bind(this));
          }
        }, {
          key: 'loadResults',
          value: function loadResults(offset) {
            return app.store.find('posts', {
              filter: {
                user: this.user.id(),
                type: 'comment'
              },
              page: { offset: offset, limit: this.loadLimit },
              sort: '-time'
            });
          }
        }, {
          key: 'loadMore',
          value: function loadMore() {
            this.loading = true;
            this.loadResults(this.posts.length).then(this.parseResults.bind(this));
          }
        }, {
          key: 'parseResults',
          value: function parseResults(results) {
            this.loading = false;

            [].push.apply(this.posts, results);

            this.moreResults = results.length >= this.loadLimit;
            m.redraw();

            return results;
          }
        }]);
        return PostsUserPage;
      }(UserPage);

      _export('default', PostsUserPage);
    }
  };
});;
'use strict';

System.register('flarum/components/PostUser', ['flarum/Component', 'flarum/components/UserCard', 'flarum/helpers/avatar', 'flarum/helpers/username', 'flarum/helpers/userOnline', 'flarum/helpers/listItems'], function (_export, _context) {
  "use strict";

  var Component, UserCard, avatar, username, userOnline, listItems, PostUser;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumComponentsUserCard) {
      UserCard = _flarumComponentsUserCard.default;
    }, function (_flarumHelpersAvatar) {
      avatar = _flarumHelpersAvatar.default;
    }, function (_flarumHelpersUsername) {
      username = _flarumHelpersUsername.default;
    }, function (_flarumHelpersUserOnline) {
      userOnline = _flarumHelpersUserOnline.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }],
    execute: function () {
      PostUser = function (_Component) {
        babelHelpers.inherits(PostUser, _Component);

        function PostUser() {
          babelHelpers.classCallCheck(this, PostUser);
          return babelHelpers.possibleConstructorReturn(this, (PostUser.__proto__ || Object.getPrototypeOf(PostUser)).apply(this, arguments));
        }

        babelHelpers.createClass(PostUser, [{
          key: 'init',
          value: function init() {
            /**
             * Whether or not the user hover card is visible.
             *
             * @type {Boolean}
             */
            this.cardVisible = false;
          }
        }, {
          key: 'view',
          value: function view() {
            var post = this.props.post;
            var user = post.user();

            if (!user) {
              return m(
                'div',
                { className: 'PostUser' },
                m(
                  'h3',
                  null,
                  avatar(user, { className: 'PostUser-avatar' }),
                  ' ',
                  username(user)
                )
              );
            }

            var card = '';

            if (!post.isHidden() && this.cardVisible) {
              card = UserCard.component({
                user: user,
                className: 'UserCard--popover',
                controlsButtonClassName: 'Button Button--icon Button--flat'
              });
            }

            return m(
              'div',
              { className: 'PostUser' },
              userOnline(user),
              m(
                'h3',
                null,
                m(
                  'a',
                  { href: app.route.user(user), config: m.route },
                  avatar(user, { className: 'PostUser-avatar' }),
                  ' ',
                  username(user)
                )
              ),
              m(
                'ul',
                { className: 'PostUser-badges badges' },
                listItems(user.badges().toArray())
              ),
              card
            );
          }
        }, {
          key: 'config',
          value: function config(isInitialized) {
            var _this2 = this;

            if (isInitialized) return;

            var timeout = void 0;

            this.$().on('mouseover', 'h3 a, .UserCard', function () {
              clearTimeout(timeout);
              timeout = setTimeout(_this2.showCard.bind(_this2), 500);
            }).on('mouseout', 'h3 a, .UserCard', function () {
              clearTimeout(timeout);
              timeout = setTimeout(_this2.hideCard.bind(_this2), 250);
            });
          }
        }, {
          key: 'showCard',
          value: function showCard() {
            var _this3 = this;

            this.cardVisible = true;

            m.redraw();

            setTimeout(function () {
              return _this3.$('.UserCard').addClass('in');
            });
          }
        }, {
          key: 'hideCard',
          value: function hideCard() {
            var _this4 = this;

            this.$('.UserCard').removeClass('in').one('transitionend webkitTransitionEnd oTransitionEnd', function () {
              _this4.cardVisible = false;
              m.redraw();
            });
          }
        }]);
        return PostUser;
      }(Component);

      _export('default', PostUser);
    }
  };
});;
'use strict';

System.register('flarum/components/RenameDiscussionModal', ['flarum/components/Modal', 'flarum/components/Button'], function (_export, _context) {
  "use strict";

  var Modal, Button, RenameDiscussionModal;
  return {
    setters: [function (_flarumComponentsModal) {
      Modal = _flarumComponentsModal.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }],
    execute: function () {
      RenameDiscussionModal = function (_Modal) {
        babelHelpers.inherits(RenameDiscussionModal, _Modal);

        function RenameDiscussionModal() {
          babelHelpers.classCallCheck(this, RenameDiscussionModal);
          return babelHelpers.possibleConstructorReturn(this, (RenameDiscussionModal.__proto__ || Object.getPrototypeOf(RenameDiscussionModal)).apply(this, arguments));
        }

        babelHelpers.createClass(RenameDiscussionModal, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(RenameDiscussionModal.prototype.__proto__ || Object.getPrototypeOf(RenameDiscussionModal.prototype), 'init', this).call(this);

            this.discussion = this.props.discussion;
            this.currentTitle = this.props.currentTitle;
            this.newTitle = m.prop(this.currentTitle);
          }
        }, {
          key: 'className',
          value: function className() {
            return 'RenameDiscussionModal Modal--small';
          }
        }, {
          key: 'title',
          value: function title() {
            return app.translator.trans('core.forum.rename_discussion.title');
          }
        }, {
          key: 'content',
          value: function content() {
            return m(
              'div',
              { className: 'Modal-body' },
              m(
                'div',
                { className: 'Form' },
                m(
                  'div',
                  { className: 'Form-group' },
                  m('input', { className: 'FormControl title', placeholder: this.currentTitle, bidi: this.newTitle })
                ),
                m(
                  'div',
                  { className: 'Form-group' },
                  Button.component({
                    className: 'Button Button--primary',
                    type: 'submit',
                    loading: this.loading,
                    children: app.translator.trans('core.forum.rename_discussion.submit_button')
                  })
                )
              )
            );
          }
        }, {
          key: 'onsubmit',
          value: function onsubmit(e) {
            var _this2 = this;

            e.preventDefault();

            this.loading = true;

            var title = this.newTitle;
            var currentTitle = this.currentTitle;

            // If the title is different to what it was before, then save it. After the
            // save has completed, update the post stream as there will be a new post
            // indicating that the discussion was renamed.
            if (title && title !== currentTitle) {
              return this.discussion.save({ title: title }).then(function () {
                if (app.viewingDiscussion(_this2.discussion)) {
                  app.current.stream.update();
                }
                m.redraw();
                _this2.hide();
              });
            } else {
              this.hide();
            }
          }
        }]);
        return RenameDiscussionModal;
      }(Modal);

      _export('default', RenameDiscussionModal);
    }
  };
});;
'use strict';

System.register('flarum/components/ReplyComposer', ['flarum/components/ComposerBody', 'flarum/components/Alert', 'flarum/components/Button', 'flarum/helpers/icon', 'flarum/utils/extractText'], function (_export, _context) {
  "use strict";

  var ComposerBody, Alert, Button, icon, extractText, ReplyComposer;


  function minimizeComposerIfFullScreen(e) {
    if (app.composer.isFullScreen()) {
      app.composer.minimize();
      e.stopPropagation();
    }
  }

  /**
   * The `ReplyComposer` component displays the composer content for replying to a
   * discussion.
   *
   * ### Props
   *
   * - All of the props of ComposerBody
   * - `discussion`
   */
  return {
    setters: [function (_flarumComponentsComposerBody) {
      ComposerBody = _flarumComponentsComposerBody.default;
    }, function (_flarumComponentsAlert) {
      Alert = _flarumComponentsAlert.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }, function (_flarumUtilsExtractText) {
      extractText = _flarumUtilsExtractText.default;
    }],
    execute: function () {
      ReplyComposer = function (_ComposerBody) {
        babelHelpers.inherits(ReplyComposer, _ComposerBody);

        function ReplyComposer() {
          babelHelpers.classCallCheck(this, ReplyComposer);
          return babelHelpers.possibleConstructorReturn(this, (ReplyComposer.__proto__ || Object.getPrototypeOf(ReplyComposer)).apply(this, arguments));
        }

        babelHelpers.createClass(ReplyComposer, [{
          key: 'init',
          value: function init() {
            var _this2 = this;

            babelHelpers.get(ReplyComposer.prototype.__proto__ || Object.getPrototypeOf(ReplyComposer.prototype), 'init', this).call(this);

            this.editor.props.preview = function (e) {
              minimizeComposerIfFullScreen(e);

              m.route(app.route.discussion(_this2.props.discussion, 'reply'));
            };
          }
        }, {
          key: 'headerItems',
          value: function headerItems() {
            var items = babelHelpers.get(ReplyComposer.prototype.__proto__ || Object.getPrototypeOf(ReplyComposer.prototype), 'headerItems', this).call(this);
            var discussion = this.props.discussion;

            var routeAndMinimize = function routeAndMinimize(element, isInitialized) {
              if (isInitialized) return;
              $(element).on('click', minimizeComposerIfFullScreen);
              m.route.apply(this, arguments);
            };

            items.add('title', m(
              'h3',
              null,
              icon('reply'),
              ' ',
              ' ',
              m(
                'a',
                { href: app.route.discussion(discussion), config: routeAndMinimize },
                discussion.title()
              )
            ));

            return items;
          }
        }, {
          key: 'data',
          value: function data() {
            return {
              content: this.content(),
              relationships: { discussion: this.props.discussion }
            };
          }
        }, {
          key: 'onsubmit',
          value: function onsubmit() {
            var discussion = this.props.discussion;

            this.loading = true;
            m.redraw();

            var data = this.data();

            app.store.createRecord('posts').save(data).then(function (post) {
              // If we're currently viewing the discussion which this reply was made
              // in, then we can update the post stream.
              if (app.viewingDiscussion(discussion)) {
                app.current.stream.update();
              } else {
                // Otherwise, we'll create an alert message to inform the user that
                // their reply has been posted, containing a button which will
                // transition to their new post when clicked.
                var alert = void 0;
                var viewButton = Button.component({
                  className: 'Button Button--link',
                  children: app.translator.trans('core.forum.composer_reply.view_button'),
                  onclick: function onclick() {
                    m.route(app.route.post(post));
                    app.alerts.dismiss(alert);
                  }
                });
                app.alerts.show(alert = new Alert({
                  type: 'success',
                  message: app.translator.trans('core.forum.composer_reply.posted_message'),
                  controls: [viewButton]
                }));
              }

              app.composer.hide();
            }, this.loaded.bind(this));
          }
        }], [{
          key: 'initProps',
          value: function initProps(props) {
            babelHelpers.get(ReplyComposer.__proto__ || Object.getPrototypeOf(ReplyComposer), 'initProps', this).call(this, props);

            props.placeholder = props.placeholder || extractText(app.translator.trans('core.forum.composer_reply.body_placeholder'));
            props.submitLabel = props.submitLabel || app.translator.trans('core.forum.composer_reply.submit_button');
            props.confirmExit = props.confirmExit || extractText(app.translator.trans('core.forum.composer_reply.discard_confirmation'));
          }
        }]);
        return ReplyComposer;
      }(ComposerBody);

      _export('default', ReplyComposer);
    }
  };
});;
'use strict';

System.register('flarum/components/ReplyPlaceholder', ['flarum/Component', 'flarum/helpers/avatar', 'flarum/helpers/username', 'flarum/utils/DiscussionControls'], function (_export, _context) {
  "use strict";

  var Component, avatar, username, DiscussionControls, ReplyPlaceholder;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumHelpersAvatar) {
      avatar = _flarumHelpersAvatar.default;
    }, function (_flarumHelpersUsername) {
      username = _flarumHelpersUsername.default;
    }, function (_flarumUtilsDiscussionControls) {
      DiscussionControls = _flarumUtilsDiscussionControls.default;
    }],
    execute: function () {
      ReplyPlaceholder = function (_Component) {
        babelHelpers.inherits(ReplyPlaceholder, _Component);

        function ReplyPlaceholder() {
          babelHelpers.classCallCheck(this, ReplyPlaceholder);
          return babelHelpers.possibleConstructorReturn(this, (ReplyPlaceholder.__proto__ || Object.getPrototypeOf(ReplyPlaceholder)).apply(this, arguments));
        }

        babelHelpers.createClass(ReplyPlaceholder, [{
          key: 'view',
          value: function view() {
            var _this2 = this;

            if (app.composingReplyTo(this.props.discussion)) {
              return m(
                'article',
                { className: 'Post CommentPost editing' },
                m(
                  'header',
                  { className: 'Post-header' },
                  m(
                    'div',
                    { className: 'PostUser' },
                    m(
                      'h3',
                      null,
                      avatar(app.session.user, { className: 'PostUser-avatar' }),
                      username(app.session.user)
                    )
                  )
                ),
                m('div', { className: 'Post-body', config: this.configPreview.bind(this) })
              );
            }

            var reply = function reply() {
              DiscussionControls.replyAction.call(_this2.props.discussion, true);
            };

            return m(
              'article',
              { className: 'Post ReplyPlaceholder', onclick: reply },
              m(
                'header',
                { className: 'Post-header' },
                avatar(app.session.user, { className: 'PostUser-avatar' }),
                ' ',
                app.translator.trans('core.forum.post_stream.reply_placeholder')
              )
            );
          }
        }, {
          key: 'configPreview',
          value: function configPreview(element, isInitialized, context) {
            if (isInitialized) return;

            // Every 50ms, if the composer content has changed, then update the post's
            // body with a preview.
            var preview = void 0;
            var updateInterval = setInterval(function () {
              var content = app.composer.component.content();

              if (preview === content) return;

              preview = content;

              var anchorToBottom = $(window).scrollTop() + $(window).height() >= $(document).height();

              s9e.TextFormatter.preview(preview || '', element);

              if (anchorToBottom) {
                $(window).scrollTop($(document).height());
              }
            }, 50);

            context.onunload = function () {
              return clearInterval(updateInterval);
            };
          }
        }]);
        return ReplyPlaceholder;
      }(Component);

      _export('default', ReplyPlaceholder);
    }
  };
});;
'use strict';

System.register('flarum/components/RequestErrorModal', ['flarum/components/Modal'], function (_export, _context) {
  "use strict";

  var Modal, RequestErrorModal;
  return {
    setters: [function (_flarumComponentsModal) {
      Modal = _flarumComponentsModal.default;
    }],
    execute: function () {
      RequestErrorModal = function (_Modal) {
        babelHelpers.inherits(RequestErrorModal, _Modal);

        function RequestErrorModal() {
          babelHelpers.classCallCheck(this, RequestErrorModal);
          return babelHelpers.possibleConstructorReturn(this, (RequestErrorModal.__proto__ || Object.getPrototypeOf(RequestErrorModal)).apply(this, arguments));
        }

        babelHelpers.createClass(RequestErrorModal, [{
          key: 'className',
          value: function className() {
            return 'RequestErrorModal Modal--large';
          }
        }, {
          key: 'title',
          value: function title() {
            return this.props.error.xhr ? this.props.error.xhr.status + ' ' + this.props.error.xhr.statusText : '';
          }
        }, {
          key: 'content',
          value: function content() {
            var responseText = void 0;

            try {
              responseText = JSON.stringify(JSON.parse(this.props.error.responseText), null, 2);
            } catch (e) {
              responseText = this.props.error.responseText;
            }

            return m(
              'div',
              { className: 'Modal-body' },
              m(
                'pre',
                null,
                this.props.error.options.method,
                ' ',
                this.props.error.options.url,
                m('br', null),
                m('br', null),
                responseText
              )
            );
          }
        }]);
        return RequestErrorModal;
      }(Modal);

      _export('default', RequestErrorModal);
    }
  };
});;
'use strict';

System.register('flarum/components/Search', ['flarum/Component', 'flarum/components/LoadingIndicator', 'flarum/utils/ItemList', 'flarum/utils/classList', 'flarum/utils/extractText', 'flarum/utils/KeyboardNavigatable', 'flarum/helpers/icon', 'flarum/components/DiscussionsSearchSource', 'flarum/components/UsersSearchSource'], function (_export, _context) {
  "use strict";

  var Component, LoadingIndicator, ItemList, classList, extractText, KeyboardNavigatable, icon, DiscussionsSearchSource, UsersSearchSource, Search;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumComponentsLoadingIndicator) {
      LoadingIndicator = _flarumComponentsLoadingIndicator.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumUtilsClassList) {
      classList = _flarumUtilsClassList.default;
    }, function (_flarumUtilsExtractText) {
      extractText = _flarumUtilsExtractText.default;
    }, function (_flarumUtilsKeyboardNavigatable) {
      KeyboardNavigatable = _flarumUtilsKeyboardNavigatable.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }, function (_flarumComponentsDiscussionsSearchSource) {
      DiscussionsSearchSource = _flarumComponentsDiscussionsSearchSource.default;
    }, function (_flarumComponentsUsersSearchSource) {
      UsersSearchSource = _flarumComponentsUsersSearchSource.default;
    }],
    execute: function () {
      Search = function (_Component) {
        babelHelpers.inherits(Search, _Component);

        function Search() {
          babelHelpers.classCallCheck(this, Search);
          return babelHelpers.possibleConstructorReturn(this, (Search.__proto__ || Object.getPrototypeOf(Search)).apply(this, arguments));
        }

        babelHelpers.createClass(Search, [{
          key: 'init',
          value: function init() {
            /**
             * The value of the search input.
             *
             * @type {Function}
             */
            this.value = m.prop('');

            /**
             * Whether or not the search input has focus.
             *
             * @type {Boolean}
             */
            this.hasFocus = false;

            /**
             * An array of SearchSources.
             *
             * @type {SearchSource[]}
             */
            this.sources = this.sourceItems().toArray();

            /**
             * The number of sources that are still loading results.
             *
             * @type {Integer}
             */
            this.loadingSources = 0;

            /**
             * A list of queries that have been searched for.
             *
             * @type {Array}
             */
            this.searched = [];

            /**
             * The index of the currently-selected <li> in the results list. This can be
             * a unique string (to account for the fact that an item's position may jump
             * around as new results load), but otherwise it will be numeric (the
             * sequential position within the list).
             *
             * @type {String|Integer}
             */
            this.index = 0;
          }
        }, {
          key: 'view',
          value: function view() {
            var _this2 = this;

            var currentSearch = this.getCurrentSearch();

            // Initialize search input value in the view rather than the constructor so
            // that we have access to app.current.
            if (typeof this.value() === 'undefined') {
              this.value(currentSearch || '');
            }

            return m(
              'div',
              { className: 'Search ' + classList({
                  open: this.value() && this.hasFocus,
                  focused: this.hasFocus,
                  active: !!currentSearch,
                  loading: !!this.loadingSources
                }) },
              m(
                'div',
                { className: 'Search-input' },
                m('input', { className: 'FormControl',
                  type: 'search',
                  placeholder: extractText(app.translator.trans('core.forum.header.search_placeholder')),
                  value: this.value(),
                  oninput: m.withAttr('value', this.value),
                  onfocus: function onfocus() {
                    return _this2.hasFocus = true;
                  },
                  onblur: function onblur() {
                    return _this2.hasFocus = false;
                  } }),
                this.loadingSources ? LoadingIndicator.component({ size: 'tiny', className: 'Button Button--icon Button--link' }) : currentSearch ? m(
                  'button',
                  { className: 'Search-clear Button Button--icon Button--link', onclick: this.clear.bind(this) },
                  icon('times-circle')
                ) : ''
              ),
              m(
                'ul',
                { className: 'Dropdown-menu Search-results' },
                this.value() && this.hasFocus ? this.sources.map(function (source) {
                  return source.view(_this2.value());
                }) : ''
              )
            );
          }
        }, {
          key: 'config',
          value: function config(isInitialized) {
            var _this3 = this;

            // Highlight the item that is currently selected.
            this.setIndex(this.getCurrentNumericIndex());

            if (isInitialized) return;

            var search = this;

            this.$('.Search-results').on('mousedown', function (e) {
              return e.preventDefault();
            }).on('click', function () {
              return _this3.$('input').blur();
            })

            // Whenever the mouse is hovered over a search result, highlight it.
            .on('mouseenter', '> li:not(.Dropdown-header)', function () {
              search.setIndex(search.selectableItems().index(this));
            });

            var $input = this.$('input');

            this.navigator = new KeyboardNavigatable();
            this.navigator.onUp(function () {
              return _this3.setIndex(_this3.getCurrentNumericIndex() - 1, true);
            }).onDown(function () {
              return _this3.setIndex(_this3.getCurrentNumericIndex() + 1, true);
            }).onSelect(this.selectResult.bind(this)).onCancel(this.clear.bind(this)).bindTo($input);

            // Handle input key events on the search input, triggering results to load.
            $input.on('input focus', function () {
              var query = this.value.toLowerCase();

              if (!query) return;

              clearTimeout(search.searchTimeout);
              search.searchTimeout = setTimeout(function () {
                if (search.searched.indexOf(query) !== -1) return;

                if (query.length >= 3) {
                  search.sources.map(function (source) {
                    if (!source.search) return;

                    search.loadingSources++;

                    source.search(query).then(function () {
                      search.loadingSources--;
                      m.redraw();
                    });
                  });
                }

                search.searched.push(query);
                m.redraw();
              }, 250);
            }).on('focus', function () {
              $(this).one('mouseup', function (e) {
                return e.preventDefault();
              }).select();
            });
          }
        }, {
          key: 'getCurrentSearch',
          value: function getCurrentSearch() {
            return app.current && typeof app.current.searching === 'function' && app.current.searching();
          }
        }, {
          key: 'selectResult',
          value: function selectResult() {
            if (this.value()) {
              m.route(this.getItem(this.index).find('a').attr('href'));
            } else {
              this.clear();
            }

            this.$('input').blur();
          }
        }, {
          key: 'clear',
          value: function clear() {
            this.value('');

            if (this.getCurrentSearch()) {
              app.current.clearSearch();
            } else {
              m.redraw();
            }
          }
        }, {
          key: 'sourceItems',
          value: function sourceItems() {
            var items = new ItemList();

            items.add('discussions', new DiscussionsSearchSource());
            items.add('users', new UsersSearchSource());

            return items;
          }
        }, {
          key: 'selectableItems',
          value: function selectableItems() {
            return this.$('.Search-results > li:not(.Dropdown-header)');
          }
        }, {
          key: 'getCurrentNumericIndex',
          value: function getCurrentNumericIndex() {
            return this.selectableItems().index(this.getItem(this.index));
          }
        }, {
          key: 'getItem',
          value: function getItem(index) {
            var $items = this.selectableItems();
            var $item = $items.filter('[data-index="' + index + '"]');

            if (!$item.length) {
              $item = $items.eq(index);
            }

            return $item;
          }
        }, {
          key: 'setIndex',
          value: function setIndex(index, scrollToItem) {
            var $items = this.selectableItems();
            var $dropdown = $items.parent();

            var fixedIndex = index;
            if (index < 0) {
              fixedIndex = $items.length - 1;
            } else if (index >= $items.length) {
              fixedIndex = 0;
            }

            var $item = $items.removeClass('active').eq(fixedIndex).addClass('active');

            this.index = $item.attr('data-index') || fixedIndex;

            if (scrollToItem) {
              var dropdownScroll = $dropdown.scrollTop();
              var dropdownTop = $dropdown.offset().top;
              var dropdownBottom = dropdownTop + $dropdown.outerHeight();
              var itemTop = $item.offset().top;
              var itemBottom = itemTop + $item.outerHeight();

              var scrollTop = void 0;
              if (itemTop < dropdownTop) {
                scrollTop = dropdownScroll - dropdownTop + itemTop - parseInt($dropdown.css('padding-top'), 10);
              } else if (itemBottom > dropdownBottom) {
                scrollTop = dropdownScroll - dropdownBottom + itemBottom + parseInt($dropdown.css('padding-bottom'), 10);
              }

              if (typeof scrollTop !== 'undefined') {
                $dropdown.stop(true).animate({ scrollTop: scrollTop }, 100);
              }
            }
          }
        }]);
        return Search;
      }(Component);

      _export('default', Search);
    }
  };
});;
"use strict";

System.register("flarum/components/SearchSource", [], function (_export, _context) {
  "use strict";

  var SearchSource;
  return {
    setters: [],
    execute: function () {
      SearchSource = function () {
        function SearchSource() {
          babelHelpers.classCallCheck(this, SearchSource);
        }

        babelHelpers.createClass(SearchSource, [{
          key: "search",
          value: function search() {}
        }, {
          key: "view",
          value: function view() {}
        }]);
        return SearchSource;
      }();

      _export("default", SearchSource);
    }
  };
});;
'use strict';

System.register('flarum/components/Select', ['flarum/Component', 'flarum/helpers/icon'], function (_export, _context) {
  "use strict";

  var Component, icon, Select;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }],
    execute: function () {
      Select = function (_Component) {
        babelHelpers.inherits(Select, _Component);

        function Select() {
          babelHelpers.classCallCheck(this, Select);
          return babelHelpers.possibleConstructorReturn(this, (Select.__proto__ || Object.getPrototypeOf(Select)).apply(this, arguments));
        }

        babelHelpers.createClass(Select, [{
          key: 'view',
          value: function view() {
            var _props = this.props,
                options = _props.options,
                onchange = _props.onchange,
                value = _props.value;


            return m(
              'span',
              { className: 'Select' },
              m(
                'select',
                { className: 'Select-input FormControl', onchange: onchange ? m.withAttr('value', onchange.bind(this)) : undefined, value: value },
                Object.keys(options).map(function (key) {
                  return m(
                    'option',
                    { value: key },
                    options[key]
                  );
                })
              ),
              icon('sort', { className: 'Select-caret' })
            );
          }
        }]);
        return Select;
      }(Component);

      _export('default', Select);
    }
  };
});;
'use strict';

System.register('flarum/components/SelectDropdown', ['flarum/components/Dropdown', 'flarum/helpers/icon'], function (_export, _context) {
  "use strict";

  var Dropdown, icon, SelectDropdown;
  return {
    setters: [function (_flarumComponentsDropdown) {
      Dropdown = _flarumComponentsDropdown.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }],
    execute: function () {
      SelectDropdown = function (_Dropdown) {
        babelHelpers.inherits(SelectDropdown, _Dropdown);

        function SelectDropdown() {
          babelHelpers.classCallCheck(this, SelectDropdown);
          return babelHelpers.possibleConstructorReturn(this, (SelectDropdown.__proto__ || Object.getPrototypeOf(SelectDropdown)).apply(this, arguments));
        }

        babelHelpers.createClass(SelectDropdown, [{
          key: 'getButtonContent',
          value: function getButtonContent() {
            var activeChild = this.props.children.filter(function (child) {
              return child.props.active;
            })[0];
            var label = activeChild && activeChild.props.children || this.props.defaultLabel;

            if (label instanceof Array) label = label[0];

            return [m(
              'span',
              { className: 'Button-label' },
              label
            ), icon(this.props.caretIcon, { className: 'Button-caret' })];
          }
        }], [{
          key: 'initProps',
          value: function initProps(props) {
            props.caretIcon = typeof props.caretIcon !== 'undefined' ? props.caretIcon : 'sort';

            babelHelpers.get(SelectDropdown.__proto__ || Object.getPrototypeOf(SelectDropdown), 'initProps', this).call(this, props);

            props.className += ' Dropdown--select';
          }
        }]);
        return SelectDropdown;
      }(Dropdown);

      _export('default', SelectDropdown);
    }
  };
});;
"use strict";

System.register("flarum/components/Separator", ["flarum/Component"], function (_export, _context) {
  "use strict";

  var Component, Separator;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }],
    execute: function () {
      Separator = function (_Component) {
        babelHelpers.inherits(Separator, _Component);

        function Separator() {
          babelHelpers.classCallCheck(this, Separator);
          return babelHelpers.possibleConstructorReturn(this, (Separator.__proto__ || Object.getPrototypeOf(Separator)).apply(this, arguments));
        }

        babelHelpers.createClass(Separator, [{
          key: "view",
          value: function view() {
            return m("li", { className: "Dropdown-separator" });
          }
        }]);
        return Separator;
      }(Component);

      Separator.isListItem = true;

      _export("default", Separator);
    }
  };
});;
'use strict';

System.register('flarum/components/SessionDropdown', ['flarum/helpers/avatar', 'flarum/helpers/username', 'flarum/components/Dropdown', 'flarum/components/LinkButton', 'flarum/components/Button', 'flarum/utils/ItemList', 'flarum/components/Separator', 'flarum/models/Group'], function (_export, _context) {
  "use strict";

  var avatar, username, Dropdown, LinkButton, Button, ItemList, Separator, Group, SessionDropdown;
  return {
    setters: [function (_flarumHelpersAvatar) {
      avatar = _flarumHelpersAvatar.default;
    }, function (_flarumHelpersUsername) {
      username = _flarumHelpersUsername.default;
    }, function (_flarumComponentsDropdown) {
      Dropdown = _flarumComponentsDropdown.default;
    }, function (_flarumComponentsLinkButton) {
      LinkButton = _flarumComponentsLinkButton.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumComponentsSeparator) {
      Separator = _flarumComponentsSeparator.default;
    }, function (_flarumModelsGroup) {
      Group = _flarumModelsGroup.default;
    }],
    execute: function () {
      SessionDropdown = function (_Dropdown) {
        babelHelpers.inherits(SessionDropdown, _Dropdown);

        function SessionDropdown() {
          babelHelpers.classCallCheck(this, SessionDropdown);
          return babelHelpers.possibleConstructorReturn(this, (SessionDropdown.__proto__ || Object.getPrototypeOf(SessionDropdown)).apply(this, arguments));
        }

        babelHelpers.createClass(SessionDropdown, [{
          key: 'view',
          value: function view() {
            this.props.children = this.items().toArray();

            return babelHelpers.get(SessionDropdown.prototype.__proto__ || Object.getPrototypeOf(SessionDropdown.prototype), 'view', this).call(this);
          }
        }, {
          key: 'getButtonContent',
          value: function getButtonContent() {
            var user = app.session.user;

            return [avatar(user), ' ', m(
              'span',
              { className: 'Button-label' },
              username(user)
            )];
          }
        }, {
          key: 'items',
          value: function items() {
            var items = new ItemList();
            var user = app.session.user;

            items.add('profile', LinkButton.component({
              icon: 'user',
              children: app.translator.trans('core.forum.header.profile_button'),
              href: app.route.user(user)
            }), 100);

            items.add('settings', LinkButton.component({
              icon: 'cog',
              children: app.translator.trans('core.forum.header.settings_button'),
              href: app.route('settings')
            }), 50);

            if (user.groups().some(function (group) {
              return group.id() === Group.ADMINISTRATOR_ID;
            })) {
              items.add('administration', LinkButton.component({
                icon: 'wrench',
                children: app.translator.trans('core.forum.header.admin_button'),
                href: app.forum.attribute('baseUrl') + '/admin',
                target: '_blank',
                config: function config() {}
              }), 0);
            }

            items.add('separator', Separator.component(), -90);

            items.add('logOut', Button.component({
              icon: 'sign-out',
              children: app.translator.trans('core.forum.header.log_out_button'),
              onclick: app.session.logout.bind(app.session)
            }), -100);

            return items;
          }
        }], [{
          key: 'initProps',
          value: function initProps(props) {
            babelHelpers.get(SessionDropdown.__proto__ || Object.getPrototypeOf(SessionDropdown), 'initProps', this).call(this, props);

            props.className = 'SessionDropdown';
            props.buttonClassName = 'Button Button--user Button--flat';
            props.menuClassName = 'Dropdown-menu--right';
          }
        }]);
        return SessionDropdown;
      }(Dropdown);

      _export('default', SessionDropdown);
    }
  };
});;
'use strict';

System.register('flarum/components/SettingsPage', ['flarum/components/UserPage', 'flarum/utils/ItemList', 'flarum/components/Switch', 'flarum/components/Button', 'flarum/components/FieldSet', 'flarum/components/NotificationGrid', 'flarum/components/ChangePasswordModal', 'flarum/components/ChangeEmailModal', 'flarum/helpers/listItems'], function (_export, _context) {
  "use strict";

  var UserPage, ItemList, Switch, Button, FieldSet, NotificationGrid, ChangePasswordModal, ChangeEmailModal, listItems, SettingsPage;
  return {
    setters: [function (_flarumComponentsUserPage) {
      UserPage = _flarumComponentsUserPage.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumComponentsSwitch) {
      Switch = _flarumComponentsSwitch.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumComponentsFieldSet) {
      FieldSet = _flarumComponentsFieldSet.default;
    }, function (_flarumComponentsNotificationGrid) {
      NotificationGrid = _flarumComponentsNotificationGrid.default;
    }, function (_flarumComponentsChangePasswordModal) {
      ChangePasswordModal = _flarumComponentsChangePasswordModal.default;
    }, function (_flarumComponentsChangeEmailModal) {
      ChangeEmailModal = _flarumComponentsChangeEmailModal.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }],
    execute: function () {
      SettingsPage = function (_UserPage) {
        babelHelpers.inherits(SettingsPage, _UserPage);

        function SettingsPage() {
          babelHelpers.classCallCheck(this, SettingsPage);
          return babelHelpers.possibleConstructorReturn(this, (SettingsPage.__proto__ || Object.getPrototypeOf(SettingsPage)).apply(this, arguments));
        }

        babelHelpers.createClass(SettingsPage, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(SettingsPage.prototype.__proto__ || Object.getPrototypeOf(SettingsPage.prototype), 'init', this).call(this);

            this.show(app.session.user);
            app.setTitle(app.translator.trans('core.forum.settings.title'));
          }
        }, {
          key: 'content',
          value: function content() {
            return m(
              'div',
              { className: 'SettingsPage' },
              m(
                'ul',
                null,
                listItems(this.settingsItems().toArray())
              )
            );
          }
        }, {
          key: 'settingsItems',
          value: function settingsItems() {
            var items = new ItemList();

            items.add('account', FieldSet.component({
              label: app.translator.trans('core.forum.settings.account_heading'),
              className: 'Settings-account',
              children: this.accountItems().toArray()
            }));

            items.add('notifications', FieldSet.component({
              label: app.translator.trans('core.forum.settings.notifications_heading'),
              className: 'Settings-notifications',
              children: this.notificationsItems().toArray()
            }));

            items.add('privacy', FieldSet.component({
              label: app.translator.trans('core.forum.settings.privacy_heading'),
              className: 'Settings-privacy',
              children: this.privacyItems().toArray()
            }));

            return items;
          }
        }, {
          key: 'accountItems',
          value: function accountItems() {
            var items = new ItemList();

            items.add('changePassword', Button.component({
              children: app.translator.trans('core.forum.settings.change_password_button'),
              className: 'Button',
              onclick: function onclick() {
                return app.modal.show(new ChangePasswordModal());
              }
            }));

            items.add('changeEmail', Button.component({
              children: app.translator.trans('core.forum.settings.change_email_button'),
              className: 'Button',
              onclick: function onclick() {
                return app.modal.show(new ChangeEmailModal());
              }
            }));

            return items;
          }
        }, {
          key: 'notificationsItems',
          value: function notificationsItems() {
            var items = new ItemList();

            items.add('notificationGrid', NotificationGrid.component({ user: this.user }));

            return items;
          }
        }, {
          key: 'preferenceSaver',
          value: function preferenceSaver(key) {
            var _this2 = this;

            return function (value, component) {
              if (component) component.loading = true;
              m.redraw();

              _this2.user.savePreferences(babelHelpers.defineProperty({}, key, value)).then(function () {
                if (component) component.loading = false;
                m.redraw();
              });
            };
          }
        }, {
          key: 'privacyItems',
          value: function privacyItems() {
            var _this3 = this;

            var items = new ItemList();

            items.add('discloseOnline', Switch.component({
              children: app.translator.trans('core.forum.settings.privacy_disclose_online_label'),
              state: this.user.preferences().discloseOnline,
              onchange: function onchange(value, component) {
                _this3.user.pushAttributes({ lastSeenTime: null });
                _this3.preferenceSaver('discloseOnline')(value, component);
              }
            }));

            return items;
          }
        }]);
        return SettingsPage;
      }(UserPage);

      _export('default', SettingsPage);
    }
  };
});;
'use strict';

System.register('flarum/components/SignUpModal', ['flarum/components/Modal', 'flarum/components/LogInModal', 'flarum/helpers/avatar', 'flarum/components/Button', 'flarum/components/LogInButtons', 'flarum/utils/extractText'], function (_export, _context) {
  "use strict";

  var Modal, LogInModal, avatar, Button, LogInButtons, extractText, SignUpModal;
  return {
    setters: [function (_flarumComponentsModal) {
      Modal = _flarumComponentsModal.default;
    }, function (_flarumComponentsLogInModal) {
      LogInModal = _flarumComponentsLogInModal.default;
    }, function (_flarumHelpersAvatar) {
      avatar = _flarumHelpersAvatar.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumComponentsLogInButtons) {
      LogInButtons = _flarumComponentsLogInButtons.default;
    }, function (_flarumUtilsExtractText) {
      extractText = _flarumUtilsExtractText.default;
    }],
    execute: function () {
      SignUpModal = function (_Modal) {
        babelHelpers.inherits(SignUpModal, _Modal);

        function SignUpModal() {
          babelHelpers.classCallCheck(this, SignUpModal);
          return babelHelpers.possibleConstructorReturn(this, (SignUpModal.__proto__ || Object.getPrototypeOf(SignUpModal)).apply(this, arguments));
        }

        babelHelpers.createClass(SignUpModal, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(SignUpModal.prototype.__proto__ || Object.getPrototypeOf(SignUpModal.prototype), 'init', this).call(this);

            /**
             * The value of the username input.
             *
             * @type {Function}
             */
            this.username = m.prop(this.props.username || '');

            /**
             * The value of the email input.
             *
             * @type {Function}
             */
            this.email = m.prop(this.props.email || '');

            /**
             * The value of the password input.
             *
             * @type {Function}
             */
            this.password = m.prop(this.props.password || '');
          }
        }, {
          key: 'className',
          value: function className() {
            return 'Modal--small SignUpModal' + (this.welcomeUser ? ' SignUpModal--success' : '');
          }
        }, {
          key: 'title',
          value: function title() {
            return app.translator.trans('core.forum.sign_up.title');
          }
        }, {
          key: 'content',
          value: function content() {
            return [m(
              'div',
              { className: 'Modal-body' },
              this.body()
            ), m(
              'div',
              { className: 'Modal-footer' },
              this.footer()
            )];
          }
        }, {
          key: 'body',
          value: function body() {
            return [this.props.token ? '' : m(LogInButtons, null), m(
              'div',
              { className: 'Form Form--centered' },
              m(
                'div',
                { className: 'Form-group' },
                m('input', { className: 'FormControl', name: 'username', type: 'text', placeholder: extractText(app.translator.trans('core.forum.sign_up.username_placeholder')),
                  value: this.username(),
                  onchange: m.withAttr('value', this.username),
                  disabled: this.loading })
              ),
              m(
                'div',
                { className: 'Form-group' },
                m('input', { className: 'FormControl', name: 'email', type: 'email', placeholder: extractText(app.translator.trans('core.forum.sign_up.email_placeholder')),
                  value: this.email(),
                  onchange: m.withAttr('value', this.email),
                  disabled: this.loading || this.props.token && this.props.email })
              ),
              this.props.token ? '' : m(
                'div',
                { className: 'Form-group' },
                m('input', { className: 'FormControl', name: 'password', type: 'password', placeholder: extractText(app.translator.trans('core.forum.sign_up.password_placeholder')),
                  value: this.password(),
                  onchange: m.withAttr('value', this.password),
                  disabled: this.loading })
              ),
              m(
                'div',
                { className: 'Form-group' },
                m(
                  Button,
                  {
                    className: 'Button Button--primary Button--block',
                    type: 'submit',
                    loading: this.loading },
                  app.translator.trans('core.forum.sign_up.submit_button')
                )
              )
            )];
          }
        }, {
          key: 'footer',
          value: function footer() {
            return [m(
              'p',
              { className: 'SignUpModal-logIn' },
              app.translator.trans('core.forum.sign_up.log_in_text', { a: m('a', { onclick: this.logIn.bind(this) }) })
            )];
          }
        }, {
          key: 'logIn',
          value: function logIn() {
            var props = {
              identification: this.email() || this.username(),
              password: this.password()
            };

            app.modal.show(new LogInModal(props));
          }
        }, {
          key: 'onready',
          value: function onready() {
            if (this.props.username && !this.props.email) {
              this.$('[name=email]').select();
            } else {
              this.$('[name=username]').select();
            }
          }
        }, {
          key: 'onsubmit',
          value: function onsubmit(e) {
            e.preventDefault();

            this.loading = true;

            var data = this.submitData();

            app.request({
              url: app.forum.attribute('baseUrl') + '/register',
              method: 'POST',
              data: data,
              errorHandler: this.onerror.bind(this)
            }).then(function () {
              return window.location.reload();
            }, this.loaded.bind(this));
          }
        }, {
          key: 'submitData',
          value: function submitData() {
            var data = {
              username: this.username(),
              email: this.email()
            };

            if (this.props.token) {
              data.token = this.props.token;
            } else {
              data.password = this.password();
            }

            if (this.props.avatarUrl) {
              data.avatarUrl = this.props.avatarUrl;
            }

            return data;
          }
        }]);
        return SignUpModal;
      }(Modal);

      _export('default', SignUpModal);
    }
  };
});;
'use strict';

System.register('flarum/components/SplitDropdown', ['flarum/components/Dropdown', 'flarum/components/Button', 'flarum/helpers/icon'], function (_export, _context) {
  "use strict";

  var Dropdown, Button, icon, SplitDropdown;
  return {
    setters: [function (_flarumComponentsDropdown) {
      Dropdown = _flarumComponentsDropdown.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }],
    execute: function () {
      SplitDropdown = function (_Dropdown) {
        babelHelpers.inherits(SplitDropdown, _Dropdown);

        function SplitDropdown() {
          babelHelpers.classCallCheck(this, SplitDropdown);
          return babelHelpers.possibleConstructorReturn(this, (SplitDropdown.__proto__ || Object.getPrototypeOf(SplitDropdown)).apply(this, arguments));
        }

        babelHelpers.createClass(SplitDropdown, [{
          key: 'getButton',
          value: function getButton() {
            // Make a copy of the props of the first child component. We will assign
            // these props to a new button, so that it has exactly the same behaviour as
            // the first child.
            var firstChild = this.getFirstChild();
            var buttonProps = babelHelpers.extends({}, firstChild.props);
            buttonProps.className = (buttonProps.className || '') + ' SplitDropdown-button Button ' + this.props.buttonClassName;

            return [Button.component(buttonProps), m(
              'button',
              {
                className: 'Dropdown-toggle Button Button--icon ' + this.props.buttonClassName,
                'data-toggle': 'dropdown' },
              icon(this.props.icon, { className: 'Button-icon' }),
              icon('caret-down', { className: 'Button-caret' })
            )];
          }
        }, {
          key: 'getFirstChild',
          value: function getFirstChild() {
            var firstChild = this.props.children;

            while (firstChild instanceof Array) {
              firstChild = firstChild[0];
            }return firstChild;
          }
        }], [{
          key: 'initProps',
          value: function initProps(props) {
            babelHelpers.get(SplitDropdown.__proto__ || Object.getPrototypeOf(SplitDropdown), 'initProps', this).call(this, props);

            props.className += ' Dropdown--split';
            props.menuClassName += ' Dropdown-menu--right';
          }
        }]);
        return SplitDropdown;
      }(Dropdown);

      _export('default', SplitDropdown);
    }
  };
});;
'use strict';

System.register('flarum/components/Switch', ['flarum/components/Checkbox'], function (_export, _context) {
  "use strict";

  var Checkbox, Switch;
  return {
    setters: [function (_flarumComponentsCheckbox) {
      Checkbox = _flarumComponentsCheckbox.default;
    }],
    execute: function () {
      Switch = function (_Checkbox) {
        babelHelpers.inherits(Switch, _Checkbox);

        function Switch() {
          babelHelpers.classCallCheck(this, Switch);
          return babelHelpers.possibleConstructorReturn(this, (Switch.__proto__ || Object.getPrototypeOf(Switch)).apply(this, arguments));
        }

        babelHelpers.createClass(Switch, [{
          key: 'getDisplay',
          value: function getDisplay() {
            return this.loading ? babelHelpers.get(Switch.prototype.__proto__ || Object.getPrototypeOf(Switch.prototype), 'getDisplay', this).call(this) : '';
          }
        }], [{
          key: 'initProps',
          value: function initProps(props) {
            babelHelpers.get(Switch.__proto__ || Object.getPrototypeOf(Switch), 'initProps', this).call(this, props);

            props.className = (props.className || '') + ' Checkbox--switch';
          }
        }]);
        return Switch;
      }(Checkbox);

      _export('default', Switch);
    }
  };
});;
'use strict';

System.register('flarum/components/TerminalPost', ['flarum/Component', 'flarum/helpers/humanTime', 'flarum/helpers/icon'], function (_export, _context) {
  "use strict";

  var Component, humanTime, icon, TerminalPost;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumHelpersHumanTime) {
      humanTime = _flarumHelpersHumanTime.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }],
    execute: function () {
      TerminalPost = function (_Component) {
        babelHelpers.inherits(TerminalPost, _Component);

        function TerminalPost() {
          babelHelpers.classCallCheck(this, TerminalPost);
          return babelHelpers.possibleConstructorReturn(this, (TerminalPost.__proto__ || Object.getPrototypeOf(TerminalPost)).apply(this, arguments));
        }

        babelHelpers.createClass(TerminalPost, [{
          key: 'view',
          value: function view() {
            var discussion = this.props.discussion;
            var lastPost = this.props.lastPost && discussion.repliesCount();

            var user = discussion[lastPost ? 'lastUser' : 'startUser']();
            var time = discussion[lastPost ? 'lastTime' : 'startTime']();

            return m(
              'span',
              null,
              lastPost ? icon('reply') : '',
              ' ',
              app.translator.trans('core.forum.discussion_list.' + (lastPost ? 'replied' : 'started') + '_text', {
                user: user,
                ago: humanTime(time)
              })
            );
          }
        }]);
        return TerminalPost;
      }(Component);

      _export('default', TerminalPost);
    }
  };
});;
'use strict';

System.register('flarum/components/TextEditor', ['flarum/Component', 'flarum/utils/ItemList', 'flarum/helpers/listItems', 'flarum/components/Button'], function (_export, _context) {
  "use strict";

  var Component, ItemList, listItems, Button, TextEditor;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }],
    execute: function () {
      TextEditor = function (_Component) {
        babelHelpers.inherits(TextEditor, _Component);

        function TextEditor() {
          babelHelpers.classCallCheck(this, TextEditor);
          return babelHelpers.possibleConstructorReturn(this, (TextEditor.__proto__ || Object.getPrototypeOf(TextEditor)).apply(this, arguments));
        }

        babelHelpers.createClass(TextEditor, [{
          key: 'init',
          value: function init() {
            /**
             * The value of the textarea.
             *
             * @type {String}
             */
            this.value = m.prop(this.props.value || '');
          }
        }, {
          key: 'view',
          value: function view() {
            return m(
              'div',
              { className: 'TextEditor' },
              m('textarea', { className: 'FormControl Composer-flexible',
                config: this.configTextarea.bind(this),
                oninput: m.withAttr('value', this.oninput.bind(this)),
                placeholder: this.props.placeholder || '',
                disabled: !!this.props.disabled,
                value: this.value() }),
              m(
                'ul',
                { className: 'TextEditor-controls Composer-footer' },
                listItems(this.controlItems().toArray())
              )
            );
          }
        }, {
          key: 'configTextarea',
          value: function configTextarea(element, isInitialized) {
            var _this2 = this;

            if (isInitialized) return;

            var handler = function handler() {
              _this2.onsubmit();
              m.redraw();
            };

            $(element).bind('keydown', 'meta+return', handler);
            $(element).bind('keydown', 'ctrl+return', handler);
          }
        }, {
          key: 'controlItems',
          value: function controlItems() {
            var items = new ItemList();

            items.add('submit', Button.component({
              children: this.props.submitLabel,
              icon: 'check',
              className: 'Button Button--primary',
              itemClassName: 'App-primaryControl',
              onclick: this.onsubmit.bind(this)
            }));

            if (this.props.preview) {
              items.add('preview', Button.component({
                icon: 'eye',
                className: 'Button Button--icon',
                onclick: this.props.preview
              }));
            }

            return items;
          }
        }, {
          key: 'setValue',
          value: function setValue(value) {
            this.$('textarea').val(value).trigger('input');
          }
        }, {
          key: 'setSelectionRange',
          value: function setSelectionRange(start, end) {
            var $textarea = this.$('textarea');

            $textarea[0].setSelectionRange(start, end);
            $textarea.focus();
          }
        }, {
          key: 'getSelectionRange',
          value: function getSelectionRange() {
            var $textarea = this.$('textarea');

            return [$textarea[0].selectionStart, $textarea[0].selectionEnd];
          }
        }, {
          key: 'insertAtCursor',
          value: function insertAtCursor(insert) {
            var textarea = this.$('textarea')[0];
            var value = this.value();
            var index = textarea ? textarea.selectionStart : value.length;

            this.setValue(value.slice(0, index) + insert + value.slice(index));

            // Move the textarea cursor to the end of the content we just inserted.
            if (textarea) {
              var pos = index + insert.length;
              this.setSelectionRange(pos, pos);
            }
          }
        }, {
          key: 'oninput',
          value: function oninput(value) {
            this.value(value);

            this.props.onchange(this.value());

            m.redraw.strategy('none');
          }
        }, {
          key: 'onsubmit',
          value: function onsubmit() {
            this.props.onsubmit(this.value());
          }
        }]);
        return TextEditor;
      }(Component);

      _export('default', TextEditor);
    }
  };
});;
'use strict';

System.register('flarum/components/UserBio', ['flarum/Component', 'flarum/components/LoadingIndicator', 'flarum/utils/classList', 'flarum/utils/extractText'], function (_export, _context) {
  "use strict";

  var Component, LoadingIndicator, classList, extractText, UserBio;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumComponentsLoadingIndicator) {
      LoadingIndicator = _flarumComponentsLoadingIndicator.default;
    }, function (_flarumUtilsClassList) {
      classList = _flarumUtilsClassList.default;
    }, function (_flarumUtilsExtractText) {
      extractText = _flarumUtilsExtractText.default;
    }],
    execute: function () {
      UserBio = function (_Component) {
        babelHelpers.inherits(UserBio, _Component);

        function UserBio() {
          babelHelpers.classCallCheck(this, UserBio);
          return babelHelpers.possibleConstructorReturn(this, (UserBio.__proto__ || Object.getPrototypeOf(UserBio)).apply(this, arguments));
        }

        babelHelpers.createClass(UserBio, [{
          key: 'init',
          value: function init() {
            /**
             * Whether or not the bio is currently being edited.
             *
             * @type {Boolean}
             */
            this.editing = false;

            /**
             * Whether or not the bio is currently being saved.
             *
             * @type {Boolean}
             */
            this.loading = false;
          }
        }, {
          key: 'view',
          value: function view() {
            var user = this.props.user;
            var content = void 0;

            if (this.editing) {
              content = m('textarea', { className: 'FormControl', placeholder: extractText(app.translator.trans('core.forum.user.bio_placeholder')), rows: '3', value: user.bio() });
            } else {
              var subContent = void 0;

              if (this.loading) {
                subContent = m(
                  'p',
                  { className: 'UserBio-placeholder' },
                  LoadingIndicator.component({ size: 'tiny' })
                );
              } else {
                var bioHtml = user.bioHtml();

                if (bioHtml) {
                  subContent = m.trust(bioHtml);
                } else if (this.props.editable) {
                  subContent = m(
                    'p',
                    { className: 'UserBio-placeholder' },
                    app.translator.trans('core.forum.user.bio_placeholder')
                  );
                }
              }

              content = m(
                'div',
                { className: 'UserBio-content', onclick: this.edit.bind(this) },
                subContent
              );
            }

            return m(
              'div',
              { className: 'UserBio ' + classList({
                  editable: this.props.editable,
                  editing: this.editing
                }) },
              content
            );
          }
        }, {
          key: 'edit',
          value: function edit() {
            if (!this.props.editable) return;

            this.editing = true;
            m.redraw();

            var bio = this;
            var save = function save(e) {
              if (e.shiftKey) return;
              e.preventDefault();
              bio.save($(this).val());
            };

            this.$('textarea').focus().bind('blur', save).bind('keydown', 'return', save);
          }
        }, {
          key: 'save',
          value: function save(value) {
            var _this2 = this;

            var user = this.props.user;

            if (user.bio() !== value) {
              this.loading = true;

              user.save({ bio: value }).catch(function () {}).then(function () {
                _this2.loading = false;
                m.redraw();
              });
            }

            this.editing = false;
            m.redraw();
          }
        }]);
        return UserBio;
      }(Component);

      _export('default', UserBio);
    }
  };
});;
'use strict';

System.register('flarum/components/UserCard', ['flarum/Component', 'flarum/utils/humanTime', 'flarum/utils/ItemList', 'flarum/utils/UserControls', 'flarum/helpers/avatar', 'flarum/helpers/username', 'flarum/helpers/icon', 'flarum/components/Dropdown', 'flarum/components/UserBio', 'flarum/components/AvatarEditor', 'flarum/helpers/listItems'], function (_export, _context) {
  "use strict";

  var Component, humanTime, ItemList, UserControls, avatar, username, icon, Dropdown, UserBio, AvatarEditor, listItems, UserCard;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumUtilsHumanTime) {
      humanTime = _flarumUtilsHumanTime.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumUtilsUserControls) {
      UserControls = _flarumUtilsUserControls.default;
    }, function (_flarumHelpersAvatar) {
      avatar = _flarumHelpersAvatar.default;
    }, function (_flarumHelpersUsername) {
      username = _flarumHelpersUsername.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }, function (_flarumComponentsDropdown) {
      Dropdown = _flarumComponentsDropdown.default;
    }, function (_flarumComponentsUserBio) {
      UserBio = _flarumComponentsUserBio.default;
    }, function (_flarumComponentsAvatarEditor) {
      AvatarEditor = _flarumComponentsAvatarEditor.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }],
    execute: function () {
      UserCard = function (_Component) {
        babelHelpers.inherits(UserCard, _Component);

        function UserCard() {
          babelHelpers.classCallCheck(this, UserCard);
          return babelHelpers.possibleConstructorReturn(this, (UserCard.__proto__ || Object.getPrototypeOf(UserCard)).apply(this, arguments));
        }

        babelHelpers.createClass(UserCard, [{
          key: 'view',
          value: function view() {
            var user = this.props.user;
            var controls = UserControls.controls(user, this).toArray();
            var color = user.color();
            var badges = user.badges().toArray();

            return m(
              'div',
              { className: 'UserCard ' + (this.props.className || ''),
                style: color ? { backgroundColor: color } : '' },
              m(
                'div',
                { className: 'darkenBackground' },
                m(
                  'div',
                  { className: 'container' },
                  controls.length ? Dropdown.component({
                    children: controls,
                    className: 'UserCard-controls App-primaryControl',
                    menuClassName: 'Dropdown-menu--right',
                    buttonClassName: this.props.controlsButtonClassName,
                    label: app.translator.trans('core.forum.user_controls.button'),
                    icon: 'ellipsis-v'
                  }) : '',
                  m(
                    'div',
                    { className: 'UserCard-profile' },
                    m(
                      'h2',
                      { className: 'UserCard-identity' },
                      this.props.editable ? [AvatarEditor.component({ user: user, className: 'UserCard-avatar' }), username(user)] : m(
                        'a',
                        { href: app.route.user(user), config: m.route },
                        m(
                          'div',
                          { className: 'UserCard-avatar' },
                          avatar(user)
                        ),
                        username(user)
                      )
                    ),
                    badges.length ? m(
                      'ul',
                      { className: 'UserCard-badges badges' },
                      listItems(badges)
                    ) : '',
                    m(
                      'ul',
                      { className: 'UserCard-info' },
                      listItems(this.infoItems().toArray())
                    )
                  )
                )
              )
            );
          }
        }, {
          key: 'infoItems',
          value: function infoItems() {
            var items = new ItemList();
            var user = this.props.user;
            var lastSeenTime = user.lastSeenTime();

            items.add('bio', UserBio.component({
              user: user,
              editable: this.props.editable
            }));

            if (lastSeenTime) {
              var online = user.isOnline();

              items.add('lastSeen', m(
                'span',
                { className: 'UserCard-lastSeen' + (online ? ' online' : '') },
                online ? [icon('circle'), ' ', app.translator.trans('core.forum.user.online_text')] : [icon('clock-o'), ' ', humanTime(lastSeenTime)]
              ));
            }

            items.add('joined', app.translator.trans('core.forum.user.joined_date_text', { ago: humanTime(user.joinTime()) }));

            return items;
          }
        }]);
        return UserCard;
      }(Component);

      _export('default', UserCard);
    }
  };
});;
'use strict';

System.register('flarum/components/UserPage', ['flarum/components/Page', 'flarum/utils/ItemList', 'flarum/utils/affixSidebar', 'flarum/components/UserCard', 'flarum/components/LoadingIndicator', 'flarum/components/SelectDropdown', 'flarum/components/LinkButton', 'flarum/components/Separator', 'flarum/helpers/listItems'], function (_export, _context) {
  "use strict";

  var Page, ItemList, affixSidebar, UserCard, LoadingIndicator, SelectDropdown, LinkButton, Separator, listItems, UserPage;
  return {
    setters: [function (_flarumComponentsPage) {
      Page = _flarumComponentsPage.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumUtilsAffixSidebar) {
      affixSidebar = _flarumUtilsAffixSidebar.default;
    }, function (_flarumComponentsUserCard) {
      UserCard = _flarumComponentsUserCard.default;
    }, function (_flarumComponentsLoadingIndicator) {
      LoadingIndicator = _flarumComponentsLoadingIndicator.default;
    }, function (_flarumComponentsSelectDropdown) {
      SelectDropdown = _flarumComponentsSelectDropdown.default;
    }, function (_flarumComponentsLinkButton) {
      LinkButton = _flarumComponentsLinkButton.default;
    }, function (_flarumComponentsSeparator) {
      Separator = _flarumComponentsSeparator.default;
    }, function (_flarumHelpersListItems) {
      listItems = _flarumHelpersListItems.default;
    }],
    execute: function () {
      UserPage = function (_Page) {
        babelHelpers.inherits(UserPage, _Page);

        function UserPage() {
          babelHelpers.classCallCheck(this, UserPage);
          return babelHelpers.possibleConstructorReturn(this, (UserPage.__proto__ || Object.getPrototypeOf(UserPage)).apply(this, arguments));
        }

        babelHelpers.createClass(UserPage, [{
          key: 'init',
          value: function init() {
            babelHelpers.get(UserPage.prototype.__proto__ || Object.getPrototypeOf(UserPage.prototype), 'init', this).call(this);

            /**
             * The user this page is for.
             *
             * @type {User}
             */
            this.user = null;

            app.history.push('user');

            this.bodyClass = 'App--user';
          }
        }, {
          key: 'view',
          value: function view() {
            return m(
              'div',
              { className: 'UserPage' },
              this.user ? [UserCard.component({
                user: this.user,
                className: 'Hero UserHero',
                editable: this.user.canEdit() || this.user === app.session.user,
                controlsButtonClassName: 'Button'
              }), m(
                'div',
                { className: 'container' },
                m(
                  'nav',
                  { className: 'sideNav UserPage-nav', config: affixSidebar },
                  m(
                    'ul',
                    null,
                    listItems(this.sidebarItems().toArray())
                  )
                ),
                m(
                  'div',
                  { className: 'sideNavOffset UserPage-content' },
                  this.content()
                )
              )] : [LoadingIndicator.component({ className: 'LoadingIndicator--block' })]
            );
          }
        }, {
          key: 'content',
          value: function content() {}
        }, {
          key: 'show',
          value: function show(user) {
            this.user = user;

            app.history.push('user', user.username());
            app.setTitle(user.username());

            m.redraw();
          }
        }, {
          key: 'loadUser',
          value: function loadUser(username) {
            var _this2 = this;

            var lowercaseUsername = username.toLowerCase();

            app.store.all('users').some(function (user) {
              if (user.username().toLowerCase() === lowercaseUsername && user.joinTime()) {
                _this2.show(user);
                return true;
              }
            });

            if (!this.user) {
              app.store.find('users', username).then(this.show.bind(this));
            }
          }
        }, {
          key: 'sidebarItems',
          value: function sidebarItems() {
            var items = new ItemList();

            items.add('nav', SelectDropdown.component({
              children: this.navItems().toArray(),
              className: 'App-titleControl',
              buttonClassName: 'Button'
            }));

            return items;
          }
        }, {
          key: 'navItems',
          value: function navItems() {
            var items = new ItemList();
            var user = this.user;

            items.add('posts', LinkButton.component({
              href: app.route('user.posts', { username: user.username() }),
              children: [app.translator.trans('core.forum.user.posts_link'), m(
                'span',
                { className: 'Button-badge' },
                user.commentsCount()
              )],
              icon: 'comment-o'
            }), 100);

            items.add('discussions', LinkButton.component({
              href: app.route('user.discussions', { username: user.username() }),
              children: [app.translator.trans('core.forum.user.discussions_link'), m(
                'span',
                { className: 'Button-badge' },
                user.discussionsCount()
              )],
              icon: 'reorder'
            }), 90);

            if (app.session.user === user) {
              items.add('separator', Separator.component(), -90);
              items.add('settings', LinkButton.component({
                href: app.route('settings'),
                children: app.translator.trans('core.forum.user.settings_link'),
                icon: 'cog'
              }), -100);
            }

            return items;
          }
        }]);
        return UserPage;
      }(Page);

      _export('default', UserPage);
    }
  };
});;
'use strict';

System.register('flarum/components/UsersSearchSource', ['flarum/helpers/highlight', 'flarum/helpers/avatar', 'flarum/helpers/username'], function (_export, _context) {
  "use strict";

  var highlight, avatar, username, UsersSearchResults;
  return {
    setters: [function (_flarumHelpersHighlight) {
      highlight = _flarumHelpersHighlight.default;
    }, function (_flarumHelpersAvatar) {
      avatar = _flarumHelpersAvatar.default;
    }, function (_flarumHelpersUsername) {
      username = _flarumHelpersUsername.default;
    }],
    execute: function () {
      UsersSearchResults = function () {
        function UsersSearchResults() {
          babelHelpers.classCallCheck(this, UsersSearchResults);
        }

        babelHelpers.createClass(UsersSearchResults, [{
          key: 'search',
          value: function search(query) {
            return app.store.find('users', {
              filter: { q: query },
              page: { limit: 5 }
            });
          }
        }, {
          key: 'view',
          value: function view(query) {
            query = query.toLowerCase();

            var results = app.store.all('users').filter(function (user) {
              return user.username().toLowerCase().substr(0, query.length) === query;
            });

            if (!results.length) return '';

            return [m(
              'li',
              { className: 'Dropdown-header' },
              app.translator.trans('core.forum.search.users_heading')
            ), results.map(function (user) {
              var name = username(user);
              name.children[0] = highlight(name.children[0], query);

              return m(
                'li',
                { className: 'UserSearchResult', 'data-index': 'users' + user.id() },
                m(
                  'a',
                  { href: app.route.user(user), config: m.route },
                  avatar(user),
                  name
                )
              );
            })];
          }
        }]);
        return UsersSearchResults;
      }();

      _export('default', UsersSearchResults);
    }
  };
});;
'use strict';

System.register('flarum/components/WelcomeHero', ['flarum/Component', 'flarum/components/Button'], function (_export, _context) {
  "use strict";

  var Component, Button, WelcomeHero;
  return {
    setters: [function (_flarumComponent) {
      Component = _flarumComponent.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }],
    execute: function () {
      WelcomeHero = function (_Component) {
        babelHelpers.inherits(WelcomeHero, _Component);

        function WelcomeHero() {
          babelHelpers.classCallCheck(this, WelcomeHero);
          return babelHelpers.possibleConstructorReturn(this, (WelcomeHero.__proto__ || Object.getPrototypeOf(WelcomeHero)).apply(this, arguments));
        }

        babelHelpers.createClass(WelcomeHero, [{
          key: 'init',
          value: function init() {
            this.hidden = localStorage.getItem('welcomeHidden');
          }
        }, {
          key: 'view',
          value: function view() {
            var _this2 = this;

            if (this.hidden) return m('div', null);

            var slideUp = function slideUp() {
              _this2.$().slideUp(_this2.hide.bind(_this2));
            };

            return m(
              'header',
              { className: 'Hero WelcomeHero' },
              m(
                'div',
                { 'class': 'container' },
                Button.component({
                  icon: 'times',
                  onclick: slideUp,
                  className: 'Hero-close Button Button--icon Button--link'
                }),
                m(
                  'div',
                  { className: 'containerNarrow' },
                  m(
                    'h2',
                    { className: 'Hero-title' },
                    app.forum.attribute('welcomeTitle')
                  ),
                  m(
                    'div',
                    { className: 'Hero-subtitle' },
                    m.trust(app.forum.attribute('welcomeMessage'))
                  )
                )
              )
            );
          }
        }, {
          key: 'hide',
          value: function hide() {
            localStorage.setItem('welcomeHidden', 'true');

            this.hidden = true;
          }
        }]);
        return WelcomeHero;
      }(Component);

      _export('default', WelcomeHero);
    }
  };
});;
"use strict";

System.register("flarum/extend", [], function (_export, _context) {
  "use strict";

  /**
   * Extend an object's method by running its output through a mutating callback
   * every time it is called.
   *
   * The callback accepts the method's return value and should perform any
   * mutations directly on this value. For this reason, this function will not be
   * effective on methods which return scalar values (numbers, strings, booleans).
   *
   * Care should be taken to extend the correct object – in most cases, a class'
   * prototype will be the desired target of extension, not the class itself.
   *
   * @example
   * extend(Discussion.prototype, 'badges', function(badges) {
   *   // do something with `badges`
   * });
   *
   * @param {Object} object The object that owns the method
   * @param {String} method The name of the method to extend
   * @param {function} callback A callback which mutates the method's output
   */
  function extend(object, method, callback) {
    var original = object[method];

    object[method] = function () {
      for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
        args[_key] = arguments[_key];
      }

      var value = original ? original.apply(this, args) : undefined;

      callback.apply(this, [value].concat(args));

      return value;
    };

    babelHelpers.extends(object[method], original);
  }

  /**
   * Override an object's method by replacing it with a new function, so that the
   * new function will be run every time the object's method is called.
   *
   * The replacement function accepts the original method as its first argument,
   * which is like a call to 'super'. Any arguments passed to the original method
   * are also passed to the replacement.
   *
   * Care should be taken to extend the correct object – in most cases, a class'
   * prototype will be the desired target of extension, not the class itself.
   *
   * @example
   * override(Discussion.prototype, 'badges', function(original) {
   *   const badges = original();
   *   // do something with badges
   *   return badges;
   * });
   *
   * @param {Object} object The object that owns the method
   * @param {String} method The name of the method to override
   * @param {function} newMethod The method to replace it with
   */

  _export("extend", extend);

  function override(object, method, newMethod) {
    var original = object[method];

    object[method] = function () {
      for (var _len2 = arguments.length, args = Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
        args[_key2] = arguments[_key2];
      }

      return newMethod.apply(this, [original.bind(this)].concat(args));
    };

    babelHelpers.extends(object[method], original);
  }
  _export("override", override);

  return {
    setters: [],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/ForumApp', ['flarum/utils/History', 'flarum/App', 'flarum/components/Search', 'flarum/components/Composer', 'flarum/components/ReplyComposer', 'flarum/components/DiscussionPage', 'flarum/components/SignUpModal'], function (_export, _context) {
  "use strict";

  var History, App, Search, Composer, ReplyComposer, DiscussionPage, SignUpModal, ForumApp;
  return {
    setters: [function (_flarumUtilsHistory) {
      History = _flarumUtilsHistory.default;
    }, function (_flarumApp) {
      App = _flarumApp.default;
    }, function (_flarumComponentsSearch) {
      Search = _flarumComponentsSearch.default;
    }, function (_flarumComponentsComposer) {
      Composer = _flarumComponentsComposer.default;
    }, function (_flarumComponentsReplyComposer) {
      ReplyComposer = _flarumComponentsReplyComposer.default;
    }, function (_flarumComponentsDiscussionPage) {
      DiscussionPage = _flarumComponentsDiscussionPage.default;
    }, function (_flarumComponentsSignUpModal) {
      SignUpModal = _flarumComponentsSignUpModal.default;
    }],
    execute: function () {
      ForumApp = function (_App) {
        babelHelpers.inherits(ForumApp, _App);

        function ForumApp() {
          var _ref;

          babelHelpers.classCallCheck(this, ForumApp);

          for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
            args[_key] = arguments[_key];
          }

          var _this = babelHelpers.possibleConstructorReturn(this, (_ref = ForumApp.__proto__ || Object.getPrototypeOf(ForumApp)).call.apply(_ref, [this].concat(args)));

          /**
           * The app's history stack, which keeps track of which routes the user visits
           * so that they can easily navigate back to the previous route.
           *
           * @type {History}
           */
          _this.history = new History();

          /**
           * An object which controls the state of the page's side pane.
           *
           * @type {Pane}
           */
          _this.pane = null;

          /**
           * The page's search component instance.
           *
           * @type {SearchBox}
           */
          _this.search = new Search();

          /**
           * An object which controls the state of the page's drawer.
           *
           * @type {Drawer}
           */
          _this.drawer = null;

          /**
           * A map of post types to their components.
           *
           * @type {Object}
           */
          _this.postComponents = {};

          /**
           * A map of notification types to their components.
           *
           * @type {Object}
           */
          _this.notificationComponents = {};
          return _this;
        }

        /**
         * Check whether or not the user is currently composing a reply to a
         * discussion.
         *
         * @param {Discussion} discussion
         * @return {Boolean}
         */


        babelHelpers.createClass(ForumApp, [{
          key: 'composingReplyTo',
          value: function composingReplyTo(discussion) {
            return this.composer.component instanceof ReplyComposer && this.composer.component.props.discussion === discussion && this.composer.position !== Composer.PositionEnum.HIDDEN;
          }
        }, {
          key: 'viewingDiscussion',
          value: function viewingDiscussion(discussion) {
            return this.current instanceof DiscussionPage && this.current.discussion === discussion;
          }
        }, {
          key: 'authenticationComplete',
          value: function authenticationComplete(payload) {
            if (payload.authenticated) {
              window.location.reload();
            } else {
              var modal = new SignUpModal(payload);
              this.modal.show(modal);
              modal.$('[name=password]').focus();
            }
          }
        }]);
        return ForumApp;
      }(App);

      _export('default', ForumApp);
    }
  };
});;
'use strict';

System.register('flarum/helpers/avatar', [], function (_export, _context) {
  "use strict";

  function avatar(user) {
    var attrs = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

    attrs.className = 'Avatar ' + (attrs.className || '');
    var content = '';

    // If the `title` attribute is set to null or false, we don't want to give the
    // avatar a title. On the other hand, if it hasn't been given at all, we can
    // safely default it to the user's username.
    var hasTitle = attrs.title === 'undefined' || attrs.title;
    if (!hasTitle) delete attrs.title;

    // If a user has been passed, then we will set up an avatar using their
    // uploaded image, or the first letter of their username if they haven't
    // uploaded one.
    if (user) {
      var username = user.username() || '?';
      var avatarUrl = user.avatarUrl();

      if (hasTitle) attrs.title = attrs.title || username;

      if (avatarUrl) {
        return m('img', babelHelpers.extends({}, attrs, { src: avatarUrl }));
      }

      content = username.charAt(0).toUpperCase();
      attrs.style = { background: user.color() };
    }

    return m(
      'span',
      attrs,
      content
    );
  }

  _export('default', avatar);

  return {
    setters: [],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/helpers/fullTime', [], function (_export, _context) {
  "use strict";

  function fullTime(time) {
    var mo = moment(time);

    var datetime = mo.format();
    var full = mo.format('LLLL');

    return m(
      'time',
      { pubdate: true, datetime: datetime },
      full
    );
  }

  _export('default', fullTime);

  return {
    setters: [],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/helpers/highlight', ['flarum/utils/string'], function (_export, _context) {
  "use strict";

  var truncate;
  function highlight(string, phrase, length) {
    if (!phrase && !length) return string;

    // Convert the word phrase into a global regular expression (if it isn't
    // already) so we can search the string for matched.
    var regexp = phrase instanceof RegExp ? phrase : new RegExp(phrase, 'gi');

    var highlighted = string;
    var start = 0;

    // If a length was given, the truncate the string surrounding the first match.
    if (length) {
      if (phrase) start = Math.max(0, string.search(regexp) - length / 2);

      highlighted = truncate(highlighted, length, start);
    }

    // Convert the string into HTML entities, then highlight all matches with
    // <mark> tags. Then we will return the result as a trusted HTML string.
    highlighted = $('<div/>').text(highlighted).html();

    if (phrase) highlighted = highlighted.replace(regexp, '<mark>$&</mark>');

    return m.trust(highlighted);
  }

  _export('default', highlight);

  return {
    setters: [function (_flarumUtilsString) {
      truncate = _flarumUtilsString.truncate;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/helpers/humanTime', ['flarum/utils/humanTime'], function (_export, _context) {
  "use strict";

  var humanTimeUtil;
  function humanTime(time) {
    var mo = moment(time);

    var datetime = mo.format();
    var full = mo.format('LLLL');
    var ago = humanTimeUtil(time);

    return m(
      'time',
      { pubdate: true, datetime: datetime, title: full, 'data-humantime': true },
      ago
    );
  }

  _export('default', humanTime);

  return {
    setters: [function (_flarumUtilsHumanTime) {
      humanTimeUtil = _flarumUtilsHumanTime.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/helpers/icon', [], function (_export, _context) {
  "use strict";

  function icon(name) {
    var attrs = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

    attrs.className = 'icon fa fa-fw fa-' + name + ' ' + (attrs.className || '');

    return m('i', attrs);
  }

  _export('default', icon);

  return {
    setters: [],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/helpers/listItems', ['flarum/components/Separator', 'flarum/utils/classList'], function (_export, _context) {
  "use strict";

  var Separator, classList;


  function isSeparator(item) {
    return item && item.component === Separator;
  }

  function withoutUnnecessarySeparators(items) {
    var newItems = [];
    var prevItem = void 0;

    items.forEach(function (item, i) {
      if (!isSeparator(item) || prevItem && !isSeparator(prevItem) && i !== items.length - 1) {
        prevItem = item;
        newItems.push(item);
      }
    });

    return newItems;
  }

  /**
   * The `listItems` helper wraps a collection of components in <li> tags,
   * stripping out any unnecessary `Separator` components.
   *
   * @param {*} items
   * @return {Array}
   */
  function listItems(items) {
    if (!(items instanceof Array)) items = [items];

    return withoutUnnecessarySeparators(items).map(function (item) {
      var isListItem = item.component && item.component.isListItem;
      var active = item.component && item.component.isActive && item.component.isActive(item.props);
      var className = item.props ? item.props.itemClassName : item.itemClassName;

      if (isListItem) {
        item.attrs = item.attrs || {};
        item.attrs.key = item.attrs.key || item.itemName;
      }

      return isListItem ? item : m(
        'li',
        { className: classList([item.itemName ? 'item-' + item.itemName : '', className, active ? 'active' : '']),
          key: item.itemName },
        item
      );
    });
  }

  _export('default', listItems);

  return {
    setters: [function (_flarumComponentsSeparator) {
      Separator = _flarumComponentsSeparator.default;
    }, function (_flarumUtilsClassList) {
      classList = _flarumUtilsClassList.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/helpers/punctuateSeries', [], function (_export, _context) {
  "use strict";

  function punctuateSeries(items) {
    if (items.length === 2) {
      return app.translator.trans('core.lib.series.two_text', {
        first: items[0],
        second: items[1]
      });
    } else if (items.length >= 3) {
      // If there are three or more items, we will join all but the first and
      // last items with the equivalent of a comma, and then we will feed that
      // into the translator along with the first and last item.
      var second = items.slice(1, items.length - 1).reduce(function (list, item) {
        return list.concat([item, app.translator.trans('core.lib.series.glue_text')]);
      }, []).slice(0, -1);

      return app.translator.trans('core.lib.series.three_text', {
        first: items[0],
        second: second,
        third: items[items.length - 1]
      });
    }

    return items;
  }

  _export('default', punctuateSeries);

  return {
    setters: [],
    execute: function () {}
  };
});;
"use strict";

System.register("flarum/helpers/username", [], function (_export, _context) {
  "use strict";

  function username(user) {
    var name = user && user.username() || app.translator.trans('core.lib.username.deleted_text');

    return m(
      "span",
      { className: "username" },
      name
    );
  }

  _export("default", username);

  return {
    setters: [],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/helpers/userOnline', ['flarum/helpers/icon'], function (_export, _context) {
    "use strict";

    var icon;
    function userOnline(user) {
        if (user.lastSeenTime() && user.isOnline()) {
            return m(
                'span',
                { className: 'UserOnline' },
                icon('circle')
            );
        }
    }

    _export('default', userOnline);

    return {
        setters: [function (_flarumHelpersIcon) {
            icon = _flarumHelpersIcon.default;
        }],
        execute: function () {}
    };
});;
'use strict';

System.register('flarum/initializers/alertEmailConfirmation', ['flarum/components/Alert', 'flarum/components/Button', 'flarum/helpers/icon'], function (_export, _context) {
  "use strict";

  var Alert, Button, icon;
  function alertEmailConfirmation(app) {
    var user = app.session.user;

    if (!user || user.isActivated()) return;

    var resendButton = Button.component({
      className: 'Button Button--link',
      children: app.translator.trans('core.forum.user_email_confirmation.resend_button'),
      onclick: function onclick() {
        resendButton.props.loading = true;
        m.redraw();

        app.request({
          method: 'POST',
          url: app.forum.attribute('apiUrl') + '/users/' + user.id() + '/send-confirmation'
        }).then(function () {
          resendButton.props.loading = false;
          resendButton.props.children = [icon('check'), ' ', app.translator.trans('core.forum.user_email_confirmation.sent_message')];
          resendButton.props.disabled = true;
          m.redraw();
        }).catch(function () {
          resendButton.props.loading = false;
          m.redraw();
        });
      }
    });

    var ContainedAlert = function (_Alert) {
      babelHelpers.inherits(ContainedAlert, _Alert);

      function ContainedAlert() {
        babelHelpers.classCallCheck(this, ContainedAlert);
        return babelHelpers.possibleConstructorReturn(this, (ContainedAlert.__proto__ || Object.getPrototypeOf(ContainedAlert)).apply(this, arguments));
      }

      babelHelpers.createClass(ContainedAlert, [{
        key: 'view',
        value: function view() {
          var vdom = babelHelpers.get(ContainedAlert.prototype.__proto__ || Object.getPrototypeOf(ContainedAlert.prototype), 'view', this).call(this);

          vdom.children = [m(
            'div',
            { className: 'container' },
            vdom.children
          )];

          return vdom;
        }
      }]);
      return ContainedAlert;
    }(Alert);

    m.mount($('<div/>').insertBefore('#content')[0], ContainedAlert.component({
      dismissible: false,
      children: app.translator.trans('core.forum.user_email_confirmation.alert_message', { email: m(
          'strong',
          null,
          user.email()
        ) }),
      controls: [resendButton]
    }));
  }

  _export('default', alertEmailConfirmation);

  return {
    setters: [function (_flarumComponentsAlert) {
      Alert = _flarumComponentsAlert.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/initializers/boot', ['flarum/utils/ScrollListener', 'flarum/utils/Pane', 'flarum/utils/Drawer', 'flarum/utils/mapRoutes', 'flarum/helpers/icon', 'flarum/components/Navigation', 'flarum/components/HeaderPrimary', 'flarum/components/HeaderSecondary', 'flarum/components/Composer', 'flarum/components/ModalManager', 'flarum/components/AlertManager'], function (_export, _context) {
  "use strict";

  var ScrollListener, Pane, Drawer, mapRoutes, icon, Navigation, HeaderPrimary, HeaderSecondary, Composer, ModalManager, AlertManager;
  function boot(app) {
    // Get the configured default route and update that route's path to be '/'.
    // Push the homepage as the first route, so that the user will always be
    // able to click on the 'back' button to go home, regardless of which page
    // they started on.
    var defaultRoute = app.forum.attribute('defaultRoute');
    var defaultAction = 'index';

    for (var i in app.routes) {
      if (app.routes[i].path === defaultRoute) defaultAction = i;
    }

    app.routes[defaultAction].path = '/';
    app.history.push(defaultAction, icon('bars'), '/');

    m.startComputation();

    m.mount(document.getElementById('app-navigation'), Navigation.component({ className: 'App-backControl', drawer: true }));
    m.mount(document.getElementById('header-navigation'), Navigation.component());
    m.mount(document.getElementById('header-primary'), HeaderPrimary.component());
    m.mount(document.getElementById('header-secondary'), HeaderSecondary.component());

    app.pane = new Pane(document.getElementById('app'));
    app.drawer = new Drawer();
    app.composer = m.mount(document.getElementById('composer'), Composer.component());
    app.modal = m.mount(document.getElementById('modal'), ModalManager.component());
    app.alerts = m.mount(document.getElementById('alerts'), AlertManager.component());

    var basePath = app.forum.attribute('basePath');
    m.route.mode = 'pathname';
    m.route(document.getElementById('content'), basePath + '/', mapRoutes(app.routes, basePath));

    m.endComputation();

    // Route the home link back home when clicked. We do not want it to register
    // if the user is opening it in a new tab, however.
    $('#home-link').click(function (e) {
      if (e.ctrlKey || e.metaKey || e.which === 2) return;
      e.preventDefault();
      app.history.home();
      if (app.session.user) {
        app.store.find('users', app.session.user.id());
        m.redraw();
      }
    });

    // Add a class to the body which indicates that the page has been scrolled
    // down.
    new ScrollListener(function (top) {
      var $app = $('#app');
      var offset = $app.offset().top;

      $app.toggleClass('affix', top >= offset).toggleClass('scrolled', top > offset);
    }).start();

    // Initialize FastClick, which makes links and buttons much more responsive on
    // touch devices.
    $(function () {
      FastClick.attach(document.body);

      $('body').addClass('ontouchstart' in window ? 'touch' : 'no-touch');
    });

    app.booted = true;
  }

  _export('default', boot);

  return {
    setters: [function (_flarumUtilsScrollListener) {
      ScrollListener = _flarumUtilsScrollListener.default;
    }, function (_flarumUtilsPane) {
      Pane = _flarumUtilsPane.default;
    }, function (_flarumUtilsDrawer) {
      Drawer = _flarumUtilsDrawer.default;
    }, function (_flarumUtilsMapRoutes) {
      mapRoutes = _flarumUtilsMapRoutes.default;
    }, function (_flarumHelpersIcon) {
      icon = _flarumHelpersIcon.default;
    }, function (_flarumComponentsNavigation) {
      Navigation = _flarumComponentsNavigation.default;
    }, function (_flarumComponentsHeaderPrimary) {
      HeaderPrimary = _flarumComponentsHeaderPrimary.default;
    }, function (_flarumComponentsHeaderSecondary) {
      HeaderSecondary = _flarumComponentsHeaderSecondary.default;
    }, function (_flarumComponentsComposer) {
      Composer = _flarumComponentsComposer.default;
    }, function (_flarumComponentsModalManager) {
      ModalManager = _flarumComponentsModalManager.default;
    }, function (_flarumComponentsAlertManager) {
      AlertManager = _flarumComponentsAlertManager.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/initializers/components', ['flarum/components/CommentPost', 'flarum/components/DiscussionRenamedPost', 'flarum/components/DiscussionRenamedNotification'], function (_export, _context) {
  "use strict";

  var CommentPost, DiscussionRenamedPost, DiscussionRenamedNotification;
  function components(app) {
    app.postComponents.comment = CommentPost;
    app.postComponents.discussionRenamed = DiscussionRenamedPost;

    app.notificationComponents.discussionRenamed = DiscussionRenamedNotification;
  }

  _export('default', components);

  return {
    setters: [function (_flarumComponentsCommentPost) {
      CommentPost = _flarumComponentsCommentPost.default;
    }, function (_flarumComponentsDiscussionRenamedPost) {
      DiscussionRenamedPost = _flarumComponentsDiscussionRenamedPost.default;
    }, function (_flarumComponentsDiscussionRenamedNotification) {
      DiscussionRenamedNotification = _flarumComponentsDiscussionRenamedNotification.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/initializers/humanTime', ['flarum/utils/humanTime'], function (_export, _context) {
  "use strict";

  var humanTimeUtil;


  function updateHumanTimes() {
    $('[data-humantime]').each(function () {
      var $this = $(this);
      var ago = humanTimeUtil($this.attr('datetime'));

      $this.html(ago);
    });
  }

  /**
   * The `humanTime` initializer sets up a loop every 1 second to update
   * timestamps rendered with the `humanTime` helper.
   */
  function humanTime() {
    setInterval(updateHumanTimes, 10000);
  }

  _export('default', humanTime);

  return {
    setters: [function (_flarumUtilsHumanTime) {
      humanTimeUtil = _flarumUtilsHumanTime.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/initializers/preload', ['flarum/Session'], function (_export, _context) {
  "use strict";

  var Session;
  function preload(app) {
    app.store.pushPayload({ data: app.data.resources });

    app.forum = app.store.getById('forums', 1);

    app.session = new Session(app.store.getById('users', app.data.session.userId), app.data.session.csrfToken);
  }

  _export('default', preload);

  return {
    setters: [function (_flarumSession) {
      Session = _flarumSession.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/initializers/routes', ['flarum/components/IndexPage', 'flarum/components/DiscussionPage', 'flarum/components/PostsUserPage', 'flarum/components/DiscussionsUserPage', 'flarum/components/SettingsPage', 'flarum/components/NotificationsPage'], function (_export, _context) {
  "use strict";

  var IndexPage, DiscussionPage, PostsUserPage, DiscussionsUserPage, SettingsPage, NotificationsPage;

  _export('default', function (app) {
    app.routes = {
      'index': { path: '/all', component: IndexPage.component() },
      'index.filter': { path: '/:filter', component: IndexPage.component() },

      'discussion': { path: '/d/:id', component: DiscussionPage.component() },
      'discussion.near': { path: '/d/:id/:near', component: DiscussionPage.component() },

      'user': { path: '/u/:username', component: PostsUserPage.component() },
      'user.posts': { path: '/u/:username', component: PostsUserPage.component() },
      'user.discussions': { path: '/u/:username/discussions', component: DiscussionsUserPage.component() },

      'settings': { path: '/settings', component: SettingsPage.component() },
      'notifications': { path: '/notifications', component: NotificationsPage.component() }
    };

    /**
     * Generate a URL to a discussion.
     *
     * @param {Discussion} discussion
     * @param {Integer} [near]
     * @return {String}
     */
    app.route.discussion = function (discussion, near) {
      return app.route(near && near !== 1 ? 'discussion.near' : 'discussion', {
        id: discussion.id() + '-' + discussion.slug(),
        near: near && near !== 1 ? near : undefined
      });
    };

    /**
     * Generate a URL to a post.
     *
     * @param {Post} post
     * @return {String}
     */
    app.route.post = function (post) {
      return app.route.discussion(post.discussion(), post.number());
    };

    /**
     * Generate a URL to a user.
     *
     * @param {User} user
     * @return {String}
     */
    app.route.user = function (user) {
      return app.route('user', {
        username: user.username()
      });
    };
  });

  return {
    setters: [function (_flarumComponentsIndexPage) {
      IndexPage = _flarumComponentsIndexPage.default;
    }, function (_flarumComponentsDiscussionPage) {
      DiscussionPage = _flarumComponentsDiscussionPage.default;
    }, function (_flarumComponentsPostsUserPage) {
      PostsUserPage = _flarumComponentsPostsUserPage.default;
    }, function (_flarumComponentsDiscussionsUserPage) {
      DiscussionsUserPage = _flarumComponentsDiscussionsUserPage.default;
    }, function (_flarumComponentsSettingsPage) {
      SettingsPage = _flarumComponentsSettingsPage.default;
    }, function (_flarumComponentsNotificationsPage) {
      NotificationsPage = _flarumComponentsNotificationsPage.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/initializers/store', ['flarum/Store', 'flarum/models/Forum', 'flarum/models/User', 'flarum/models/Discussion', 'flarum/models/Post', 'flarum/models/Group', 'flarum/models/Activity', 'flarum/models/Notification'], function (_export, _context) {
  "use strict";

  var Store, Forum, User, Discussion, Post, Group, Activity, Notification;
  function store(app) {
    app.store = new Store({
      forums: Forum,
      users: User,
      discussions: Discussion,
      posts: Post,
      groups: Group,
      activity: Activity,
      notifications: Notification
    });
  }

  _export('default', store);

  return {
    setters: [function (_flarumStore) {
      Store = _flarumStore.default;
    }, function (_flarumModelsForum) {
      Forum = _flarumModelsForum.default;
    }, function (_flarumModelsUser) {
      User = _flarumModelsUser.default;
    }, function (_flarumModelsDiscussion) {
      Discussion = _flarumModelsDiscussion.default;
    }, function (_flarumModelsPost) {
      Post = _flarumModelsPost.default;
    }, function (_flarumModelsGroup) {
      Group = _flarumModelsGroup.default;
    }, function (_flarumModelsActivity) {
      Activity = _flarumModelsActivity.default;
    }, function (_flarumModelsNotification) {
      Notification = _flarumModelsNotification.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/Model', [], function (_export, _context) {
  "use strict";

  var Model;
  return {
    setters: [],
    execute: function () {
      Model = function () {
        /**
         * @param {Object} data A resource object from the API.
         * @param {Store} store The data store that this model should be persisted to.
         * @public
         */
        function Model() {
          var data = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
          var store = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
          babelHelpers.classCallCheck(this, Model);

          /**
           * The resource object from the API.
           *
           * @type {Object}
           * @public
           */
          this.data = data;

          /**
           * The time at which the model's data was last updated. Watching the value
           * of this property is a fast way to retain/cache a subtree if data hasn't
           * changed.
           *
           * @type {Date}
           * @public
           */
          this.freshness = new Date();

          /**
           * Whether or not the resource exists on the server.
           *
           * @type {Boolean}
           * @public
           */
          this.exists = false;

          /**
           * The data store that this resource should be persisted to.
           *
           * @type {Store}
           * @protected
           */
          this.store = store;
        }

        /**
         * Get the model's ID.
         *
         * @return {Integer}
         * @public
         * @final
         */


        babelHelpers.createClass(Model, [{
          key: 'id',
          value: function id() {
            return this.data.id;
          }
        }, {
          key: 'attribute',
          value: function attribute(_attribute) {
            return this.data.attributes[_attribute];
          }
        }, {
          key: 'pushData',
          value: function pushData(data) {
            // Since most of the top-level items in a resource object are objects
            // (e.g. relationships, attributes), we'll need to check and perform the
            // merge at the second level if that's the case.
            for (var key in data) {
              if (babelHelpers.typeof(data[key]) === 'object') {
                this.data[key] = this.data[key] || {};

                // For every item in a second-level object, we want to check if we've
                // been handed a Model instance. If so, we will convert it to a
                // relationship data object.
                for (var innerKey in data[key]) {
                  if (data[key][innerKey] instanceof Model) {
                    data[key][innerKey] = { data: Model.getIdentifier(data[key][innerKey]) };
                  }
                  this.data[key][innerKey] = data[key][innerKey];
                }
              } else {
                this.data[key] = data[key];
              }
            }

            // Now that we've updated the data, we can say that the model is fresh.
            // This is an easy way to invalidate retained subtrees etc.
            this.freshness = new Date();
          }
        }, {
          key: 'pushAttributes',
          value: function pushAttributes(attributes) {
            this.pushData({ attributes: attributes });
          }
        }, {
          key: 'save',
          value: function save(attributes) {
            var _this = this;

            var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

            var data = {
              type: this.data.type,
              id: this.data.id,
              attributes: attributes
            };

            // If a 'relationships' key exists, extract it from the attributes hash and
            // set it on the top-level data object instead. We will be sending this data
            // object to the API for persistence.
            if (attributes.relationships) {
              data.relationships = {};

              for (var key in attributes.relationships) {
                var model = attributes.relationships[key];

                data.relationships[key] = {
                  data: model instanceof Array ? model.map(Model.getIdentifier) : Model.getIdentifier(model)
                };
              }

              delete attributes.relationships;
            }

            // Before we update the model's data, we should make a copy of the model's
            // old data so that we can revert back to it if something goes awry during
            // persistence.
            var oldData = this.copyData();

            this.pushData(data);

            var request = { data: data };
            if (options.meta) request.meta = options.meta;

            return app.request(babelHelpers.extends({
              method: this.exists ? 'PATCH' : 'POST',
              url: app.forum.attribute('apiUrl') + this.apiEndpoint(),
              data: request
            }, options)).then(
            // If everything went well, we'll make sure the store knows that this
            // model exists now (if it didn't already), and we'll push the data that
            // the API returned into the store.
            function (payload) {
              _this.store.data[payload.data.type] = _this.store.data[payload.data.type] || {};
              _this.store.data[payload.data.type][payload.data.id] = _this;
              return _this.store.pushPayload(payload);
            },

            // If something went wrong, though... good thing we backed up our model's
            // old data! We'll revert to that and let others handle the error.
            function (response) {
              _this.pushData(oldData);
              m.lazyRedraw();
              throw response;
            });
          }
        }, {
          key: 'delete',
          value: function _delete(data) {
            var _this2 = this;

            var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

            if (!this.exists) return m.deferred.resolve().promise;

            return app.request(babelHelpers.extends({
              method: 'DELETE',
              url: app.forum.attribute('apiUrl') + this.apiEndpoint(),
              data: data
            }, options)).then(function () {
              _this2.exists = false;
              _this2.store.remove(_this2);
            });
          }
        }, {
          key: 'apiEndpoint',
          value: function apiEndpoint() {
            return '/' + this.data.type + (this.exists ? '/' + this.data.id : '');
          }
        }, {
          key: 'copyData',
          value: function copyData() {
            return JSON.parse(JSON.stringify(this.data));
          }
        }], [{
          key: 'attribute',
          value: function attribute(name, transform) {
            return function () {
              var value = this.data.attributes && this.data.attributes[name];

              return transform ? transform(value) : value;
            };
          }
        }, {
          key: 'hasOne',
          value: function hasOne(name) {
            return function () {
              if (this.data.relationships) {
                var relationship = this.data.relationships[name];

                if (relationship) {
                  return app.store.getById(relationship.data.type, relationship.data.id);
                }
              }

              return false;
            };
          }
        }, {
          key: 'hasMany',
          value: function hasMany(name) {
            return function () {
              if (this.data.relationships) {
                var relationship = this.data.relationships[name];

                if (relationship) {
                  return relationship.data.map(function (data) {
                    return app.store.getById(data.type, data.id);
                  });
                }
              }

              return false;
            };
          }
        }, {
          key: 'transformDate',
          value: function transformDate(value) {
            return value ? new Date(value) : null;
          }
        }, {
          key: 'getIdentifier',
          value: function getIdentifier(model) {
            return {
              type: model.data.type,
              id: model.data.id
            };
          }
        }]);
        return Model;
      }();

      _export('default', Model);
    }
  };
});;
'use strict';

System.register('flarum/models/Discussion', ['flarum/Model', 'flarum/utils/computed', 'flarum/utils/ItemList', 'flarum/components/Badge'], function (_export, _context) {
  "use strict";

  var Model, computed, ItemList, Badge, Discussion;
  return {
    setters: [function (_flarumModel) {
      Model = _flarumModel.default;
    }, function (_flarumUtilsComputed) {
      computed = _flarumUtilsComputed.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumComponentsBadge) {
      Badge = _flarumComponentsBadge.default;
    }],
    execute: function () {
      Discussion = function (_Model) {
        babelHelpers.inherits(Discussion, _Model);

        function Discussion() {
          babelHelpers.classCallCheck(this, Discussion);
          return babelHelpers.possibleConstructorReturn(this, (Discussion.__proto__ || Object.getPrototypeOf(Discussion)).apply(this, arguments));
        }

        return Discussion;
      }(Model);

      _export('default', Discussion);

      babelHelpers.extends(Discussion.prototype, {
        title: Model.attribute('title'),
        slug: Model.attribute('slug'),

        startTime: Model.attribute('startTime', Model.transformDate),
        startUser: Model.hasOne('startUser'),
        startPost: Model.hasOne('startPost'),

        lastTime: Model.attribute('lastTime', Model.transformDate),
        lastUser: Model.hasOne('lastUser'),
        lastPost: Model.hasOne('lastPost'),
        lastPostNumber: Model.attribute('lastPostNumber'),

        commentsCount: Model.attribute('commentsCount'),
        repliesCount: computed('commentsCount', function (commentsCount) {
          return Math.max(0, commentsCount - 1);
        }),
        posts: Model.hasMany('posts'),
        relevantPosts: Model.hasMany('relevantPosts'),

        readTime: Model.attribute('readTime', Model.transformDate),
        readNumber: Model.attribute('readNumber'),
        isUnread: computed('unreadCount', function (unreadCount) {
          return !!unreadCount;
        }),
        isRead: computed('unreadCount', function (unreadCount) {
          return app.session.user && !unreadCount;
        }),

        hideTime: Model.attribute('hideTime', Model.transformDate),
        hideUser: Model.hasOne('hideUser'),
        isHidden: computed('hideTime', function (hideTime) {
          return !!hideTime;
        }),

        canReply: Model.attribute('canReply'),
        canRename: Model.attribute('canRename'),
        canHide: Model.attribute('canHide'),
        canDelete: Model.attribute('canDelete'),

        removePost: function removePost(id) {
          var relationships = this.data.relationships;
          var posts = relationships && relationships.posts;

          if (posts) {
            posts.data.some(function (data, i) {
              if (id === data.id) {
                posts.data.splice(i, 1);
                return true;
              }
            });
          }
        },
        unreadCount: function unreadCount() {
          var user = app.session.user;

          if (user && user.readTime() < this.lastTime()) {
            return Math.max(0, this.lastPostNumber() - (this.readNumber() || 0));
          }

          return 0;
        },
        badges: function badges() {
          var items = new ItemList();

          if (this.isHidden()) {
            items.add('hidden', m(Badge, { type: 'hidden', icon: 'trash', label: app.translator.trans('core.lib.badge.hidden_tooltip') }));
          }

          return items;
        },
        postIds: function postIds() {
          var posts = this.data.relationships.posts;

          return posts ? posts.data.map(function (link) {
            return link.id;
          }) : [];
        }
      });
    }
  };
});;
'use strict';

System.register('flarum/models/Forum', ['flarum/Model'], function (_export, _context) {
  "use strict";

  var Model, Forum;
  return {
    setters: [function (_flarumModel) {
      Model = _flarumModel.default;
    }],
    execute: function () {
      Forum = function (_Model) {
        babelHelpers.inherits(Forum, _Model);

        function Forum() {
          babelHelpers.classCallCheck(this, Forum);
          return babelHelpers.possibleConstructorReturn(this, (Forum.__proto__ || Object.getPrototypeOf(Forum)).apply(this, arguments));
        }

        babelHelpers.createClass(Forum, [{
          key: 'apiEndpoint',
          value: function apiEndpoint() {
            return '/forum';
          }
        }]);
        return Forum;
      }(Model);

      _export('default', Forum);
    }
  };
});;
'use strict';

System.register('flarum/models/Group', ['flarum/Model'], function (_export, _context) {
  "use strict";

  var Model, Group;
  return {
    setters: [function (_flarumModel) {
      Model = _flarumModel.default;
    }],
    execute: function () {
      Group = function (_Model) {
        babelHelpers.inherits(Group, _Model);

        function Group() {
          babelHelpers.classCallCheck(this, Group);
          return babelHelpers.possibleConstructorReturn(this, (Group.__proto__ || Object.getPrototypeOf(Group)).apply(this, arguments));
        }

        return Group;
      }(Model);

      babelHelpers.extends(Group.prototype, {
        nameSingular: Model.attribute('nameSingular'),
        namePlural: Model.attribute('namePlural'),
        color: Model.attribute('color'),
        icon: Model.attribute('icon')
      });

      Group.ADMINISTRATOR_ID = '1';
      Group.GUEST_ID = '2';
      Group.MEMBER_ID = '3';

      _export('default', Group);
    }
  };
});;
'use strict';

System.register('flarum/models/Notification', ['flarum/Model', 'flarum/utils/computed'], function (_export, _context) {
  "use strict";

  var Model, computed, Notification;
  return {
    setters: [function (_flarumModel) {
      Model = _flarumModel.default;
    }, function (_flarumUtilsComputed) {
      computed = _flarumUtilsComputed.default;
    }],
    execute: function () {
      Notification = function (_Model) {
        babelHelpers.inherits(Notification, _Model);

        function Notification() {
          babelHelpers.classCallCheck(this, Notification);
          return babelHelpers.possibleConstructorReturn(this, (Notification.__proto__ || Object.getPrototypeOf(Notification)).apply(this, arguments));
        }

        return Notification;
      }(Model);

      _export('default', Notification);

      babelHelpers.extends(Notification.prototype, {
        contentType: Model.attribute('contentType'),
        subjectId: Model.attribute('subjectId'),
        content: Model.attribute('content'),
        time: Model.attribute('time', Model.date),

        isRead: Model.attribute('isRead'),
        unreadCount: Model.attribute('unreadCount'),
        additionalUnreadCount: computed('unreadCount', function (unreadCount) {
          return Math.max(0, unreadCount - 1);
        }),

        user: Model.hasOne('user'),
        sender: Model.hasOne('sender'),
        subject: Model.hasOne('subject')
      });
    }
  };
});;
'use strict';

System.register('flarum/models/Post', ['flarum/Model', 'flarum/utils/computed', 'flarum/utils/string'], function (_export, _context) {
  "use strict";

  var Model, computed, getPlainContent, Post;
  return {
    setters: [function (_flarumModel) {
      Model = _flarumModel.default;
    }, function (_flarumUtilsComputed) {
      computed = _flarumUtilsComputed.default;
    }, function (_flarumUtilsString) {
      getPlainContent = _flarumUtilsString.getPlainContent;
    }],
    execute: function () {
      Post = function (_Model) {
        babelHelpers.inherits(Post, _Model);

        function Post() {
          babelHelpers.classCallCheck(this, Post);
          return babelHelpers.possibleConstructorReturn(this, (Post.__proto__ || Object.getPrototypeOf(Post)).apply(this, arguments));
        }

        return Post;
      }(Model);

      _export('default', Post);

      babelHelpers.extends(Post.prototype, {
        number: Model.attribute('number'),
        discussion: Model.hasOne('discussion'),

        time: Model.attribute('time', Model.transformDate),
        user: Model.hasOne('user'),
        contentType: Model.attribute('contentType'),
        content: Model.attribute('content'),
        contentHtml: Model.attribute('contentHtml'),
        contentPlain: computed('contentHtml', getPlainContent),

        editTime: Model.attribute('editTime', Model.transformDate),
        editUser: Model.hasOne('editUser'),
        isEdited: computed('editTime', function (editTime) {
          return !!editTime;
        }),

        hideTime: Model.attribute('hideTime', Model.transformDate),
        hideUser: Model.hasOne('hideUser'),
        isHidden: computed('hideTime', function (hideTime) {
          return !!hideTime;
        }),

        canEdit: Model.attribute('canEdit'),
        canDelete: Model.attribute('canDelete')
      });
    }
  };
});;
'use strict';

System.register('flarum/models/User', ['flarum/Model', 'flarum/utils/stringToColor', 'flarum/utils/ItemList', 'flarum/utils/computed', 'flarum/components/GroupBadge'], function (_export, _context) {
  "use strict";

  var Model, stringToColor, ItemList, computed, GroupBadge, User;
  return {
    setters: [function (_flarumModel) {
      Model = _flarumModel.default;
    }, function (_flarumUtilsStringToColor) {
      stringToColor = _flarumUtilsStringToColor.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumUtilsComputed) {
      computed = _flarumUtilsComputed.default;
    }, function (_flarumComponentsGroupBadge) {
      GroupBadge = _flarumComponentsGroupBadge.default;
    }],
    execute: function () {
      User = function (_Model) {
        babelHelpers.inherits(User, _Model);

        function User() {
          babelHelpers.classCallCheck(this, User);
          return babelHelpers.possibleConstructorReturn(this, (User.__proto__ || Object.getPrototypeOf(User)).apply(this, arguments));
        }

        return User;
      }(Model);

      _export('default', User);

      babelHelpers.extends(User.prototype, {
        username: Model.attribute('username'),
        email: Model.attribute('email'),
        isActivated: Model.attribute('isActivated'),
        password: Model.attribute('password'),

        avatarUrl: Model.attribute('avatarUrl'),
        bio: Model.attribute('bio'),
        bioHtml: computed('bio', function (bio) {
          return bio ? '<p>' + $('<div/>').text(bio).html().replace(/\n/g, '<br>').autoLink({ rel: 'nofollow' }) + '</p>' : '';
        }),
        preferences: Model.attribute('preferences'),
        groups: Model.hasMany('groups'),

        joinTime: Model.attribute('joinTime', Model.transformDate),
        lastSeenTime: Model.attribute('lastSeenTime', Model.transformDate),
        readTime: Model.attribute('readTime', Model.transformDate),
        unreadNotificationsCount: Model.attribute('unreadNotificationsCount'),
        newNotificationsCount: Model.attribute('newNotificationsCount'),

        discussionsCount: Model.attribute('discussionsCount'),
        commentsCount: Model.attribute('commentsCount'),

        canEdit: Model.attribute('canEdit'),
        canDelete: Model.attribute('canDelete'),

        avatarColor: null,
        color: computed('username', 'avatarUrl', 'avatarColor', function (username, avatarUrl, avatarColor) {
          // If we've already calculated and cached the dominant color of the user's
          // avatar, then we can return that in RGB format. If we haven't, we'll want
          // to calculate it. Unless the user doesn't have an avatar, in which case
          // we generate a color from their username.
          if (avatarColor) {
            return 'rgb(' + avatarColor.join(', ') + ')';
          } else if (avatarUrl) {
            this.calculateAvatarColor();
            return '';
          }

          return '#' + stringToColor(username);
        }),

        isOnline: function isOnline() {
          return this.lastSeenTime() > moment().subtract(5, 'minutes').toDate();
        },
        badges: function badges() {
          var items = new ItemList();
          var groups = this.groups();

          if (groups) {
            groups.forEach(function (group) {
              items.add('group' + group.id(), GroupBadge.component({ group: group }));
            });
          }

          return items;
        },
        calculateAvatarColor: function calculateAvatarColor() {
          var image = new Image();
          var user = this;

          image.onload = function () {
            var colorThief = new ColorThief();
            user.avatarColor = colorThief.getColor(this);
            user.freshness = new Date();
            m.redraw();
          };
          image.src = this.avatarUrl();
        },
        savePreferences: function savePreferences(newPreferences) {
          var preferences = this.preferences();

          babelHelpers.extends(preferences, newPreferences);

          return this.save({ preferences: preferences });
        }
      });
    }
  };
});;
'use strict';

System.register('flarum/Session', [], function (_export, _context) {
  "use strict";

  var Session;
  return {
    setters: [],
    execute: function () {
      Session = function () {
        function Session(user, csrfToken) {
          babelHelpers.classCallCheck(this, Session);

          /**
           * The current authenticated user.
           *
           * @type {User|null}
           * @public
           */
          this.user = user;

          /**
           * The CSRF token.
           *
           * @type {String|null}
           * @public
           */
          this.csrfToken = csrfToken;
        }

        /**
         * Attempt to log in a user.
         *
         * @param {String} identification The username/email.
         * @param {String} password
         * @param {Object} [options]
         * @return {Promise}
         * @public
         */


        babelHelpers.createClass(Session, [{
          key: 'login',
          value: function login(data) {
            var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

            return app.request(babelHelpers.extends({
              method: 'POST',
              url: app.forum.attribute('baseUrl') + '/login',
              data: data
            }, options));
          }
        }, {
          key: 'logout',
          value: function logout() {
            window.location = app.forum.attribute('baseUrl') + '/logout?token=' + this.csrfToken;
          }
        }]);
        return Session;
      }();

      _export('default', Session);
    }
  };
});;
'use strict';

System.register('flarum/Store', [], function (_export, _context) {
  "use strict";

  var Store;
  return {
    setters: [],
    execute: function () {
      Store = function () {
        function Store(models) {
          babelHelpers.classCallCheck(this, Store);

          /**
           * The local data store. A tree of resource types to IDs, such that
           * accessing data[type][id] will return the model for that type/ID.
           *
           * @type {Object}
           * @protected
           */
          this.data = {};

          /**
           * The model registry. A map of resource types to the model class that
           * should be used to represent resources of that type.
           *
           * @type {Object}
           * @public
           */
          this.models = models;
        }

        /**
         * Push resources contained within an API payload into the store.
         *
         * @param {Object} payload
         * @return {Model|Model[]} The model(s) representing the resource(s) contained
         *     within the 'data' key of the payload.
         * @public
         */


        babelHelpers.createClass(Store, [{
          key: 'pushPayload',
          value: function pushPayload(payload) {
            if (payload.included) payload.included.map(this.pushObject.bind(this));

            var result = payload.data instanceof Array ? payload.data.map(this.pushObject.bind(this)) : this.pushObject(payload.data);

            // Attach the original payload to the model that we give back. This is
            // useful to consumers as it allows them to access meta information
            // associated with their request.
            result.payload = payload;

            return result;
          }
        }, {
          key: 'pushObject',
          value: function pushObject(data) {
            if (!this.models[data.type]) return null;

            var type = this.data[data.type] = this.data[data.type] || {};

            if (type[data.id]) {
              type[data.id].pushData(data);
            } else {
              type[data.id] = this.createRecord(data.type, data);
            }

            type[data.id].exists = true;

            return type[data.id];
          }
        }, {
          key: 'find',
          value: function find(type, id) {
            var query = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
            var options = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : {};

            var data = query;
            var url = app.forum.attribute('apiUrl') + '/' + type;

            if (id instanceof Array) {
              url += '?filter[id]=' + id.join(',');
            } else if ((typeof id === 'undefined' ? 'undefined' : babelHelpers.typeof(id)) === 'object') {
              data = id;
            } else if (id) {
              url += '/' + id;
            }

            return app.request(babelHelpers.extends({
              method: 'GET',
              url: url,
              data: data
            }, options)).then(this.pushPayload.bind(this));
          }
        }, {
          key: 'getById',
          value: function getById(type, id) {
            return this.data[type] && this.data[type][id];
          }
        }, {
          key: 'getBy',
          value: function getBy(type, key, value) {
            return this.all(type).filter(function (model) {
              return model[key]() === value;
            })[0];
          }
        }, {
          key: 'all',
          value: function all(type) {
            var records = this.data[type];

            return records ? Object.keys(records).map(function (id) {
              return records[id];
            }) : [];
          }
        }, {
          key: 'remove',
          value: function remove(model) {
            delete this.data[model.data.type][model.id()];
          }
        }, {
          key: 'createRecord',
          value: function createRecord(type) {
            var data = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

            data.type = data.type || type;

            return new this.models[type](data, this);
          }
        }]);
        return Store;
      }();

      _export('default', Store);
    }
  };
});;
'use strict';

System.register('flarum/Translator', ['flarum/models/User', 'flarum/helpers/username', 'flarum/utils/extractText', 'flarum/utils/extract'], function (_export, _context) {
  "use strict";

  var User, username, extractText, extract, Translator;
  return {
    setters: [function (_flarumModelsUser) {
      User = _flarumModelsUser.default;
    }, function (_flarumHelpersUsername) {
      username = _flarumHelpersUsername.default;
    }, function (_flarumUtilsExtractText) {
      extractText = _flarumUtilsExtractText.default;
    }, function (_flarumUtilsExtract) {
      extract = _flarumUtilsExtract.default;
    }],
    execute: function () {
      Translator = function () {
        function Translator() {
          babelHelpers.classCallCheck(this, Translator);

          /**
           * A map of translation keys to their translated values.
           *
           * @type {Object}
           * @public
           */
          this.translations = {};

          this.locale = null;
        }

        babelHelpers.createClass(Translator, [{
          key: 'trans',
          value: function trans(id, parameters) {
            var translation = this.translations[id];

            if (translation) {
              return this.apply(translation, parameters || {});
            }

            return id;
          }
        }, {
          key: 'transChoice',
          value: function transChoice(id, number, parameters) {
            var translation = this.translations[id];

            if (translation) {
              number = parseInt(number, 10);

              translation = this.pluralize(translation, number);

              return this.apply(translation, parameters || {});
            }

            return id;
          }
        }, {
          key: 'apply',
          value: function apply(translation, input) {
            // If we've been given a user model as one of the input parameters, then
            // we'll extract the username and use that for the translation. In the
            // future there should be a hook here to inspect the user and change the
            // translation key. This will allow a gender property to determine which
            // translation key is used.
            if ('user' in input) {
              var user = extract(input, 'user');

              if (!input.username) input.username = username(user);
            }

            translation = translation.split(new RegExp('({[a-z0-9_]+}|</?[a-z0-9_]+>)', 'gi'));

            var hydrated = [];
            var open = [hydrated];

            translation.forEach(function (part) {
              var match = part.match(new RegExp('{([a-z0-9_]+)}|<(/?)([a-z0-9_]+)>', 'i'));

              if (match) {
                if (match[1]) {
                  open[0].push(input[match[1]]);
                } else if (match[3]) {
                  if (match[2]) {
                    open.shift();
                  } else {
                    var tag = input[match[3]] || { tag: match[3], children: [] };
                    open[0].push(tag);
                    open.unshift(tag.children || tag);
                  }
                }
              } else {
                open[0].push(part);
              }
            });

            return hydrated.filter(function (part) {
              return part;
            });
          }
        }, {
          key: 'pluralize',
          value: function pluralize(translation, number) {
            var _this = this;

            var sPluralRegex = new RegExp(/^\w+\: +(.+)$/),
                cPluralRegex = new RegExp(/^\s*((\{\s*(\-?\d+[\s*,\s*\-?\d+]*)\s*\})|([\[\]])\s*(-Inf|\-?\d+)\s*,\s*(\+?Inf|\-?\d+)\s*([\[\]]))\s?(.+?)$/),
                iPluralRegex = new RegExp(/^\s*(\{\s*(\-?\d+[\s*,\s*\-?\d+]*)\s*\})|([\[\]])\s*(-Inf|\-?\d+)\s*,\s*(\+?Inf|\-?\d+)\s*([\[\]])/),
                standardRules = [],
                explicitRules = [];

            translation.split('|').forEach(function (part) {
              if (cPluralRegex.test(part)) {
                var matches = part.match(cPluralRegex);
                explicitRules[matches[0]] = matches[matches.length - 1];
              } else if (sPluralRegex.test(part)) {
                var _matches = part.match(sPluralRegex);
                standardRules.push(_matches[1]);
              } else {
                standardRules.push(part);
              }
            });

            explicitRules.forEach(function (rule, e) {
              if (iPluralRegex.test(e)) {
                var matches = e.match(iPluralRegex);

                if (matches[1]) {
                  var ns = matches[2].split(',');

                  for (var n in ns) {
                    if (number == ns[n]) {
                      return explicitRules[e];
                    }
                  }
                } else {
                  var leftNumber = _this.convertNumber(matches[4]);
                  var rightNumber = _this.convertNumber(matches[5]);

                  if (('[' === matches[3] ? number >= leftNumber : number > leftNumber) && (']' === matches[6] ? number <= rightNumber : number < rightNumber)) {
                    return explicitRules[e];
                  }
                }
              }
            });

            return standardRules[this.pluralPosition(number, this.locale)] || standardRules[0] || undefined;
          }
        }, {
          key: 'convertNumber',
          value: function convertNumber(number) {
            if ('-Inf' === number) {
              return Number.NEGATIVE_INFINITY;
            } else if ('+Inf' === number || 'Inf' === number) {
              return Number.POSITIVE_INFINITY;
            }

            return parseInt(number, 10);
          }
        }, {
          key: 'pluralPosition',
          value: function pluralPosition(number, locale) {
            if ('pt_BR' === locale) {
              locale = 'xbr';
            }

            if (locale.length > 3) {
              locale = locale.split('_')[0];
            }

            switch (locale) {
              case 'bo':
              case 'dz':
              case 'id':
              case 'ja':
              case 'jv':
              case 'ka':
              case 'km':
              case 'kn':
              case 'ko':
              case 'ms':
              case 'th':
              case 'vi':
              case 'zh':
                return 0;

              case 'af':
              case 'az':
              case 'bn':
              case 'bg':
              case 'ca':
              case 'da':
              case 'de':
              case 'el':
              case 'en':
              case 'eo':
              case 'es':
              case 'et':
              case 'eu':
              case 'fa':
              case 'fi':
              case 'fo':
              case 'fur':
              case 'fy':
              case 'gl':
              case 'gu':
              case 'ha':
              case 'he':
              case 'hu':
              case 'is':
              case 'it':
              case 'ku':
              case 'lb':
              case 'ml':
              case 'mn':
              case 'mr':
              case 'nah':
              case 'nb':
              case 'ne':
              case 'nl':
              case 'nn':
              case 'no':
              case 'om':
              case 'or':
              case 'pa':
              case 'pap':
              case 'ps':
              case 'pt':
              case 'so':
              case 'sq':
              case 'sv':
              case 'sw':
              case 'ta':
              case 'te':
              case 'tk':
              case 'tr':
              case 'ur':
              case 'zu':
                return number == 1 ? 0 : 1;

              case 'am':
              case 'bh':
              case 'fil':
              case 'fr':
              case 'gun':
              case 'hi':
              case 'ln':
              case 'mg':
              case 'nso':
              case 'xbr':
              case 'ti':
              case 'wa':
                return number === 0 || number == 1 ? 0 : 1;

              case 'be':
              case 'bs':
              case 'hr':
              case 'ru':
              case 'sr':
              case 'uk':
                return number % 10 == 1 && number % 100 != 11 ? 0 : number % 10 >= 2 && number % 10 <= 4 && (number % 100 < 10 || number % 100 >= 20) ? 1 : 2;

              case 'cs':
              case 'sk':
                return number == 1 ? 0 : number >= 2 && number <= 4 ? 1 : 2;

              case 'ga':
                return number == 1 ? 0 : number == 2 ? 1 : 2;

              case 'lt':
                return number % 10 == 1 && number % 100 != 11 ? 0 : number % 10 >= 2 && (number % 100 < 10 || number % 100 >= 20) ? 1 : 2;

              case 'sl':
                return number % 100 == 1 ? 0 : number % 100 == 2 ? 1 : number % 100 == 3 || number % 100 == 4 ? 2 : 3;

              case 'mk':
                return number % 10 == 1 ? 0 : 1;

              case 'mt':
                return number == 1 ? 0 : number === 0 || number % 100 > 1 && number % 100 < 11 ? 1 : number % 100 > 10 && number % 100 < 20 ? 2 : 3;

              case 'lv':
                return number === 0 ? 0 : number % 10 == 1 && number % 100 != 11 ? 1 : 2;

              case 'pl':
                return number == 1 ? 0 : number % 10 >= 2 && number % 10 <= 4 && (number % 100 < 12 || number % 100 > 14) ? 1 : 2;

              case 'cy':
                return number == 1 ? 0 : number == 2 ? 1 : number == 8 || number == 11 ? 2 : 3;

              case 'ro':
                return number == 1 ? 0 : number === 0 || number % 100 > 0 && number % 100 < 20 ? 1 : 2;

              case 'ar':
                return number === 0 ? 0 : number == 1 ? 1 : number == 2 ? 2 : number >= 3 && number <= 10 ? 3 : number >= 11 && number <= 99 ? 4 : 5;

              default:
                return 0;
            }
          }
        }]);
        return Translator;
      }();

      _export('default', Translator);
    }
  };
});;
'use strict';

System.register('flarum/utils/abbreviateNumber', [], function (_export, _context) {
  "use strict";

  function abbreviateNumber(number) {
    // TODO: translation
    if (number >= 1000000) {
      return Math.floor(number / 1000000) + app.translator.trans('core.lib.number_suffix.mega_text');
    } else if (number >= 1000) {
      return Math.floor(number / 1000) + app.translator.trans('core.lib.number_suffix.kilo_text');
    } else {
      return number.toString();
    }
  }

  _export('default', abbreviateNumber);

  return {
    setters: [],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/utils/affixSidebar', [], function (_export, _context) {
  "use strict";

  function affixSidebar(element, isInitialized, context) {
    var _this = this;

    if (isInitialized) return;

    var onresize = function onresize() {
      var $sidebar = $(element);
      var $header = $('#header');
      var $footer = $('#footer');
      var $affixElement = $sidebar.find('> ul');

      $(window).off('.affix');
      $affixElement.removeClass('affix affix-top affix-bottom').removeData('bs.affix');

      // Don't affix the sidebar if it is taller than the viewport (otherwise
      // there would be no way to scroll through its content).
      if ($sidebar.outerHeight(true) > $(window).height() - $header.outerHeight(true)) return;

      $affixElement.affix({
        offset: {
          top: function top() {
            return $sidebar.offset().top - $header.outerHeight(true) - parseInt($sidebar.css('margin-top'), 10);
          },
          bottom: function bottom() {
            return _this.bottom = $footer.outerHeight(true);
          }
        }
      });
    };

    // Register the affix plugin to execute on every window resize (and trigger)
    $(window).on('resize', onresize).resize();

    context.onunload = function () {
      $(window).off('resize', onresize);
    };
  }

  _export('default', affixSidebar);

  return {
    setters: [],
    execute: function () {}
  };
});;
"use strict";

System.register("flarum/utils/anchorScroll", [], function (_export, _context) {
  "use strict";

  function anchorScroll(element, callback) {
    var $window = $(window);
    var relativeScroll = $(element).offset().top - $window.scrollTop();

    callback();

    $window.scrollTop($(element).offset().top - relativeScroll);
  }

  _export("default", anchorScroll);

  return {
    setters: [],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/utils/classList', [], function (_export, _context) {
  "use strict";

  function classList(classes) {
    var classNames = void 0;

    if (classes instanceof Array) {
      classNames = classes.filter(function (name) {
        return name;
      });
    } else {
      classNames = [];

      for (var i in classes) {
        if (classes[i]) classNames.push(i);
      }
    }

    return classNames.join(' ');
  }

  _export('default', classList);

  return {
    setters: [],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/utils/computed', [], function (_export, _context) {
  "use strict";

  function computed() {
    for (var _len = arguments.length, dependentKeys = Array(_len), _key = 0; _key < _len; _key++) {
      dependentKeys[_key] = arguments[_key];
    }

    var keys = dependentKeys.slice(0, -1);
    var compute = dependentKeys.slice(-1)[0];

    var dependentValues = {};
    var computedValue = void 0;

    return function () {
      var _this = this;

      var recompute = false;

      // Read all of the dependent values. If any of them have changed since last
      // time, then we'll want to recompute our output.
      keys.forEach(function (key) {
        var value = typeof _this[key] === 'function' ? _this[key]() : _this[key];

        if (dependentValues[key] !== value) {
          recompute = true;
          dependentValues[key] = value;
        }
      });

      if (recompute) {
        computedValue = compute.apply(this, keys.map(function (key) {
          return dependentValues[key];
        }));
      }

      return computedValue;
    };
  }

  _export('default', computed);

  return {
    setters: [],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/utils/DiscussionControls', ['flarum/components/DiscussionPage', 'flarum/components/ReplyComposer', 'flarum/components/LogInModal', 'flarum/components/Button', 'flarum/components/Separator', 'flarum/components/RenameDiscussionModal', 'flarum/utils/ItemList', 'flarum/utils/extractText'], function (_export, _context) {
  "use strict";

  var DiscussionPage, ReplyComposer, LogInModal, Button, Separator, RenameDiscussionModal, ItemList, extractText;
  return {
    setters: [function (_flarumComponentsDiscussionPage) {
      DiscussionPage = _flarumComponentsDiscussionPage.default;
    }, function (_flarumComponentsReplyComposer) {
      ReplyComposer = _flarumComponentsReplyComposer.default;
    }, function (_flarumComponentsLogInModal) {
      LogInModal = _flarumComponentsLogInModal.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumComponentsSeparator) {
      Separator = _flarumComponentsSeparator.default;
    }, function (_flarumComponentsRenameDiscussionModal) {
      RenameDiscussionModal = _flarumComponentsRenameDiscussionModal.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }, function (_flarumUtilsExtractText) {
      extractText = _flarumUtilsExtractText.default;
    }],
    execute: function () {
      _export('default', {
        controls: function controls(discussion, context) {
          var _this = this;

          var items = new ItemList();

          ['user', 'moderation', 'destructive'].forEach(function (section) {
            var controls = _this[section + 'Controls'](discussion, context).toArray();
            if (controls.length) {
              controls.forEach(function (item) {
                return items.add(item.itemName, item);
              });
              items.add(section + 'Separator', Separator.component());
            }
          });

          return items;
        },
        userControls: function userControls(discussion, context) {
          var items = new ItemList();

          // Only add a reply control if this is the discussion's controls dropdown
          // for the discussion page itself. We don't want it to show up for
          // discussions in the discussion list, etc.
          if (context instanceof DiscussionPage) {
            items.add('reply', !app.session.user || discussion.canReply() ? Button.component({
              icon: 'reply',
              children: app.translator.trans(app.session.user ? 'core.forum.discussion_controls.reply_button' : 'core.forum.discussion_controls.log_in_to_reply_button'),
              onclick: this.replyAction.bind(discussion, true, false)
            }) : Button.component({
              icon: 'reply',
              children: app.translator.trans('core.forum.discussion_controls.cannot_reply_button'),
              className: 'disabled',
              title: app.translator.trans('core.forum.discussion_controls.cannot_reply_text')
            }));
          }

          return items;
        },
        moderationControls: function moderationControls(discussion) {
          var items = new ItemList();

          if (discussion.canRename()) {
            items.add('rename', Button.component({
              icon: 'pencil',
              children: app.translator.trans('core.forum.discussion_controls.rename_button'),
              onclick: this.renameAction.bind(discussion)
            }));
          }

          return items;
        },
        destructiveControls: function destructiveControls(discussion) {
          var items = new ItemList();

          if (!discussion.isHidden()) {
            if (discussion.canHide()) {
              items.add('hide', Button.component({
                icon: 'trash-o',
                children: app.translator.trans('core.forum.discussion_controls.delete_button'),
                onclick: this.hideAction.bind(discussion)
              }));
            }
          } else {
            if (discussion.canHide()) {
              items.add('restore', Button.component({
                icon: 'reply',
                children: app.translator.trans('core.forum.discussion_controls.restore_button'),
                onclick: this.restoreAction.bind(discussion)
              }));
            }

            if (discussion.canDelete()) {
              items.add('delete', Button.component({
                icon: 'times',
                children: app.translator.trans('core.forum.discussion_controls.delete_forever_button'),
                onclick: this.deleteAction.bind(discussion)
              }));
            }
          }

          return items;
        },
        replyAction: function replyAction(goToLast, forceRefresh) {
          var deferred = m.deferred();

          if (app.session.user) {
            if (this.canReply()) {
              var component = app.composer.component;
              if (!app.composingReplyTo(this) || forceRefresh) {
                component = new ReplyComposer({
                  user: app.session.user,
                  discussion: this
                });
                app.composer.load(component);
              }
              app.composer.show();

              if (goToLast && app.viewingDiscussion(this)) {
                app.current.stream.goToNumber('reply');
              }

              deferred.resolve(component);
            } else {
              deferred.reject();
            }
          } else {
            app.modal.show(new LogInModal());
          }

          return deferred.promise;
        },
        hideAction: function hideAction() {
          this.pushAttributes({ hideTime: new Date(), hideUser: app.session.user });

          return this.save({ isHidden: true });
        },
        restoreAction: function restoreAction() {
          this.pushAttributes({ hideTime: null, hideUser: null });

          return this.save({ isHidden: false });
        },
        deleteAction: function deleteAction() {
          var _this2 = this;

          if (confirm(extractText(app.translator.trans('core.forum.discussion_controls.delete_confirmation')))) {
            // If we're currently viewing the discussion that was deleted, go back
            // to the previous page.
            if (app.viewingDiscussion(this)) {
              app.history.back();
            }

            return this.delete().then(function () {
              // If there is a discussion list in the cache, remove this discussion.
              if (app.cache.discussionList) {
                app.cache.discussionList.removeDiscussion(_this2);
                m.redraw();
              }
            });
          }
        },
        renameAction: function renameAction() {
          return app.modal.show(new RenameDiscussionModal({
            currentTitle: this.title(),
            discussion: this
          }));
        }
      });
    }
  };
});;
'use strict';

System.register('flarum/utils/Drawer', [], function (_export, _context) {
  "use strict";

  var Drawer;
  return {
    setters: [],
    execute: function () {
      Drawer = function () {
        function Drawer() {
          var _this = this;

          babelHelpers.classCallCheck(this, Drawer);

          // Set up an event handler so that whenever the content area is tapped,
          // the drawer will close.
          $('#content').click(function (e) {
            if (_this.isOpen()) {
              e.preventDefault();
              _this.hide();
            }
          });
        }

        /**
         * Check whether or not the drawer is currently open.
         *
         * @return {Boolean}
         * @public
         */


        babelHelpers.createClass(Drawer, [{
          key: 'isOpen',
          value: function isOpen() {
            return $('#app').hasClass('drawerOpen');
          }
        }, {
          key: 'hide',
          value: function hide() {
            $('#app').removeClass('drawerOpen');

            if (this.$backdrop) this.$backdrop.remove();
          }
        }, {
          key: 'show',
          value: function show() {
            var _this2 = this;

            $('#app').addClass('drawerOpen');

            this.$backdrop = $('<div/>').addClass('drawer-backdrop fade').appendTo('body').click(function () {
              return _this2.hide();
            });

            setTimeout(function () {
              return _this2.$backdrop.addClass('in');
            });
          }
        }]);
        return Drawer;
      }();

      _export('default', Drawer);
    }
  };
});;
"use strict";

System.register("flarum/utils/evented", [], function (_export, _context) {
  "use strict";

  return {
    setters: [],
    execute: function () {
      _export("default", {
        /**
         * Arrays of registered event handlers, grouped by the event name.
         *
         * @type {Object}
         * @protected
         */
        handlers: null,

        getHandlers: function getHandlers(event) {
          this.handlers = this.handlers || {};

          this.handlers[event] = this.handlers[event] || [];

          return this.handlers[event];
        },
        trigger: function trigger(event) {
          var _this = this;

          for (var _len = arguments.length, args = Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
            args[_key - 1] = arguments[_key];
          }

          this.getHandlers(event).forEach(function (handler) {
            return handler.apply(_this, args);
          });
        },
        on: function on(event, handler) {
          this.getHandlers(event).push(handler);
        },
        one: function one(event, handler) {
          var wrapper = function wrapper() {
            handler.apply(this, arguments);

            this.off(event, wrapper);
          };

          this.getHandlers(event).push(wrapper);
        },
        off: function off(event, handler) {
          var handlers = this.getHandlers(event);
          var index = handlers.indexOf(handler);

          if (index !== -1) {
            handlers.splice(index, 1);
          }
        }
      });
    }
  };
});;
"use strict";

System.register("flarum/utils/extract", [], function (_export, _context) {
  "use strict";

  function extract(object, property) {
    var value = object[property];

    delete object[property];

    return value;
  }

  _export("default", extract);

  return {
    setters: [],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/utils/extractText', [], function (_export, _context) {
  "use strict";

  function extractText(vdom) {
    if (vdom instanceof Array) {
      return vdom.map(function (element) {
        return extractText(element);
      }).join('');
    } else if ((typeof vdom === 'undefined' ? 'undefined' : babelHelpers.typeof(vdom)) === 'object') {
      return extractText(vdom.children);
    } else {
      return vdom;
    }
  }

  _export('default', extractText);

  return {
    setters: [],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/utils/formatNumber', [], function (_export, _context) {
  "use strict";

  function formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  _export('default', formatNumber);

  return {
    setters: [],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/utils/History', [], function (_export, _context) {
  "use strict";

  var History;
  return {
    setters: [],
    execute: function () {
      History = function () {
        function History(defaultRoute) {
          babelHelpers.classCallCheck(this, History);

          /**
           * The stack of routes that have been navigated to.
           *
           * @type {Array}
           * @protected
           */
          this.stack = [];
        }

        /**
         * Get the item on the top of the stack.
         *
         * @return {Object}
         * @public
         */


        babelHelpers.createClass(History, [{
          key: 'getCurrent',
          value: function getCurrent() {
            return this.stack[this.stack.length - 1];
          }
        }, {
          key: 'getPrevious',
          value: function getPrevious() {
            return this.stack[this.stack.length - 2];
          }
        }, {
          key: 'push',
          value: function push(name, title) {
            var url = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : m.route();

            // If we're pushing an item with the same name as second-to-top item in the
            // stack, we will assume that the user has clicked the 'back' button in
            // their browser. In this case, we don't want to push a new item, so we will
            // pop off the top item, and then the second-to-top item will be overwritten
            // below.
            var secondTop = this.stack[this.stack.length - 2];
            if (secondTop && secondTop.name === name) {
              this.stack.pop();
            }

            // If we're pushing an item with the same name as the top item in the stack,
            // then we'll overwrite it with the new URL.
            var top = this.getCurrent();
            if (top && top.name === name) {
              babelHelpers.extends(top, { url: url, title: title });
            } else {
              this.stack.push({ name: name, url: url, title: title });
            }
          }
        }, {
          key: 'canGoBack',
          value: function canGoBack() {
            return this.stack.length > 1;
          }
        }, {
          key: 'back',
          value: function back() {
            this.stack.pop();

            m.route(this.getCurrent().url);
          }
        }, {
          key: 'backUrl',
          value: function backUrl() {
            var secondTop = this.stack[this.stack.length - 2];

            return secondTop.url;
          }
        }, {
          key: 'home',
          value: function home() {
            this.stack.splice(0);

            m.route('/');
          }
        }]);
        return History;
      }();

      _export('default', History);
    }
  };
});;
'use strict';

System.register('flarum/utils/humanTime', [], function (_export, _context) {
  "use strict";

  function humanTime(time) {
    var m = moment(time);
    var now = moment();

    // To prevent showing things like "in a few seconds" due to small offsets
    // between client and server time, we always reset future dates to the
    // current time. This will result in "just now" being shown instead.
    if (m.isAfter(now)) {
      m = now;
    }

    var day = 864e5;
    var diff = m.diff(moment());
    var ago = null;

    // If this date was more than a month ago, we'll show the name of the month
    // in the string. If it wasn't this year, we'll show the year as well.
    if (diff < -30 * day) {
      if (m.year() === moment().year()) {
        ago = m.format('D MMM');
      } else {
        ago = m.format('MMM \'YY');
      }
    } else {
      ago = m.fromNow();
    }

    return ago;
  }
  _export('default', humanTime);

  return {
    setters: [],
    execute: function () {
      ; /**
         * The `humanTime` utility converts a date to a localized, human-readable time-
         * ago string.
         *
         * @param {Date} time
         * @return {String}
         */
    }
  };
});;
"use strict";

System.register("flarum/utils/ItemList", [], function (_export, _context) {
  "use strict";

  var Item, ItemList;
  return {
    setters: [],
    execute: function () {
      Item = function Item(content, priority) {
        babelHelpers.classCallCheck(this, Item);

        this.content = content;
        this.priority = priority;
      };

      ItemList = function () {
        function ItemList() {
          babelHelpers.classCallCheck(this, ItemList);

          /**
           * The items in the list.
           *
           * @type {Object}
           * @public
           */
          this.items = {};
        }

        /**
         * Check whether an item is present in the list.
         *
         * @param key
         * @returns {boolean}
         */


        babelHelpers.createClass(ItemList, [{
          key: "has",
          value: function has(key) {
            return !!this.items[key];
          }
        }, {
          key: "get",
          value: function get(key) {
            return this.items[key].content;
          }
        }, {
          key: "add",
          value: function add(key, content) {
            var priority = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 0;

            this.items[key] = new Item(content, priority);
          }
        }, {
          key: "replace",
          value: function replace(key) {
            var content = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
            var priority = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;

            if (this.items[key]) {
              if (content !== null) {
                this.items[key].content = content;
              }

              if (priority !== null) {
                this.items[key].priority = priority;
              }
            }
          }
        }, {
          key: "remove",
          value: function remove(key) {
            delete this.items[key];
          }
        }, {
          key: "merge",
          value: function merge(items) {
            for (var i in items.items) {
              if (items.items.hasOwnProperty(i) && items.items[i] instanceof Item) {
                this.items[i] = items.items[i];
              }
            }
          }
        }, {
          key: "toArray",
          value: function toArray() {
            var items = [];

            for (var i in this.items) {
              if (this.items.hasOwnProperty(i) && this.items[i] instanceof Item) {
                this.items[i].content = Object(this.items[i].content);

                this.items[i].content.itemName = i;
                items.push(this.items[i]);
                this.items[i].key = items.length;
              }
            }

            return items.sort(function (a, b) {
              if (a.priority === b.priority) {
                return a.key - b.key;
              } else if (a.priority > b.priority) {
                return -1;
              }
              return 1;
            }).map(function (item) {
              return item.content;
            });
          }
        }]);
        return ItemList;
      }();

      _export("default", ItemList);
    }
  };
});;
'use strict';

System.register('flarum/utils/KeyboardNavigatable', [], function (_export, _context) {
  "use strict";

  var KeyboardNavigatable;
  return {
    setters: [],
    execute: function () {
      KeyboardNavigatable = function () {
        function KeyboardNavigatable() {
          babelHelpers.classCallCheck(this, KeyboardNavigatable);

          this.callbacks = {};

          // By default, always handle keyboard navigation.
          this.whenCallback = function () {
            return true;
          };
        }

        /**
         * Provide a callback to be executed when navigating upwards.
         *
         * This will be triggered by the Up key.
         *
         * @public
         * @param {Function} callback
         * @return {KeyboardNavigatable}
         */


        babelHelpers.createClass(KeyboardNavigatable, [{
          key: 'onUp',
          value: function onUp(callback) {
            this.callbacks[38] = function (e) {
              e.preventDefault();
              callback(e);
            };

            return this;
          }
        }, {
          key: 'onDown',
          value: function onDown(callback) {
            this.callbacks[40] = function (e) {
              e.preventDefault();
              callback(e);
            };

            return this;
          }
        }, {
          key: 'onSelect',
          value: function onSelect(callback) {
            this.callbacks[9] = this.callbacks[13] = function (e) {
              e.preventDefault();
              callback(e);
            };

            return this;
          }
        }, {
          key: 'onCancel',
          value: function onCancel(callback) {
            this.callbacks[27] = function (e) {
              e.stopPropagation();
              e.preventDefault();
              callback(e);
            };

            return this;
          }
        }, {
          key: 'onRemove',
          value: function onRemove(callback) {
            this.callbacks[8] = function (e) {
              if (e.target.selectionStart === 0 && e.target.selectionEnd === 0) {
                callback(e);
                e.preventDefault();
              }
            };

            return this;
          }
        }, {
          key: 'when',
          value: function when(callback) {
            this.whenCallback = callback;

            return this;
          }
        }, {
          key: 'bindTo',
          value: function bindTo($element) {
            // Handle navigation key events on the navigatable element.
            $element.on('keydown', this.navigate.bind(this));
          }
        }, {
          key: 'navigate',
          value: function navigate(event) {
            // This callback determines whether keyboard should be handled or ignored.
            if (!this.whenCallback()) return;

            var keyCallback = this.callbacks[event.which];
            if (keyCallback) {
              keyCallback(event);
            }
          }
        }]);
        return KeyboardNavigatable;
      }();

      _export('default', KeyboardNavigatable);
    }
  };
});;
'use strict';

System.register('flarum/utils/mapRoutes', [], function (_export, _context) {
  "use strict";

  function mapRoutes(routes) {
    var basePath = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';

    var map = {};

    for (var key in routes) {
      var route = routes[key];

      if (route.component) route.component.props.routeName = key;

      map[basePath + route.path] = route.component;
    }

    return map;
  }

  _export('default', mapRoutes);

  return {
    setters: [],
    execute: function () {}
  };
});;
"use strict";

System.register("flarum/utils/mixin", [], function (_export, _context) {
  "use strict";

  function mixin(Parent) {
    var Mixed = function (_Parent) {
      babelHelpers.inherits(Mixed, _Parent);

      function Mixed() {
        babelHelpers.classCallCheck(this, Mixed);
        return babelHelpers.possibleConstructorReturn(this, (Mixed.__proto__ || Object.getPrototypeOf(Mixed)).apply(this, arguments));
      }

      return Mixed;
    }(Parent);

    for (var _len = arguments.length, mixins = Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
      mixins[_key - 1] = arguments[_key];
    }

    mixins.forEach(function (object) {
      babelHelpers.extends(Mixed.prototype, object);
    });

    return Mixed;
  }

  _export("default", mixin);

  return {
    setters: [],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/utils/Pane', [], function (_export, _context) {
  "use strict";

  var Pane;
  return {
    setters: [],
    execute: function () {
      Pane = function () {
        function Pane(element) {
          babelHelpers.classCallCheck(this, Pane);

          /**
           * The localStorage key to store the pane's pinned state with.
           *
           * @type {String}
           * @protected
           */
          this.pinnedKey = 'panePinned';

          /**
           * The page element.
           *
           * @type {jQuery}
           * @protected
           */
          this.$element = $(element);

          /**
           * Whether or not the pane is currently pinned.
           *
           * @type {Boolean}
           * @protected
           */
          this.pinned = localStorage.getItem(this.pinnedKey) === 'true';

          /**
           * Whether or not the pane is currently exists.
           *
           * @type {Boolean}
           * @protected
           */
          this.active = false;

          /**
           * Whether or not the pane is currently showing, or is hidden off the edge
           * of the screen.
           *
           * @type {Boolean}
           * @protected
           */
          this.showing = false;

          this.render();
        }

        /**
         * Enable the pane.
         *
         * @public
         */


        babelHelpers.createClass(Pane, [{
          key: 'enable',
          value: function enable() {
            this.active = true;
            this.render();
          }
        }, {
          key: 'disable',
          value: function disable() {
            this.active = false;
            this.showing = false;
            this.render();
          }
        }, {
          key: 'show',
          value: function show() {
            clearTimeout(this.hideTimeout);
            this.showing = true;
            this.render();
          }
        }, {
          key: 'hide',
          value: function hide() {
            this.showing = false;
            this.render();
          }
        }, {
          key: 'onmouseleave',
          value: function onmouseleave() {
            this.hideTimeout = setTimeout(this.hide.bind(this), 250);
          }
        }, {
          key: 'togglePinned',
          value: function togglePinned() {
            this.pinned = !this.pinned;

            localStorage.setItem(this.pinnedKey, this.pinned ? 'true' : 'false');

            this.render();
          }
        }, {
          key: 'render',
          value: function render() {
            this.$element.toggleClass('panePinned', this.pinned).toggleClass('hasPane', this.active).toggleClass('paneShowing', this.showing);
          }
        }]);
        return Pane;
      }();

      _export('default', Pane);
    }
  };
});;
'use strict';

System.register('flarum/utils/patchMithril', ['../Component'], function (_export, _context) {
  "use strict";

  var Component;
  function patchMithril(global) {
    var mo = global.m;

    var m = function m(comp) {
      for (var _len = arguments.length, args = Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
        args[_key - 1] = arguments[_key];
      }

      if (comp.prototype && comp.prototype instanceof Component) {
        return comp.component.apply(comp, args);
      }

      var node = mo.apply(this, arguments);

      if (node.attrs.bidi) {
        m.bidi(node, node.attrs.bidi);
      }

      if (node.attrs.route) {
        node.attrs.href = node.attrs.route;
        node.attrs.config = m.route;

        delete node.attrs.route;
      }

      return node;
    };

    Object.keys(mo).forEach(function (key) {
      return m[key] = mo[key];
    });

    /**
     * Redraw only if not in the middle of a computation (e.g. a route change).
     *
     * @return {void}
     */
    m.lazyRedraw = function () {
      m.startComputation();
      m.endComputation();
    };

    global.m = m;
  }

  _export('default', patchMithril);

  return {
    setters: [function (_Component) {
      Component = _Component.default;
    }],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/utils/PostControls', ['flarum/components/EditPostComposer', 'flarum/components/Button', 'flarum/components/Separator', 'flarum/utils/ItemList'], function (_export, _context) {
  "use strict";

  var EditPostComposer, Button, Separator, ItemList;
  return {
    setters: [function (_flarumComponentsEditPostComposer) {
      EditPostComposer = _flarumComponentsEditPostComposer.default;
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumComponentsSeparator) {
      Separator = _flarumComponentsSeparator.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }],
    execute: function () {
      _export('default', {
        controls: function controls(post, context) {
          var _this = this;

          var items = new ItemList();

          ['user', 'moderation', 'destructive'].forEach(function (section) {
            var controls = _this[section + 'Controls'](post, context).toArray();
            if (controls.length) {
              controls.forEach(function (item) {
                return items.add(item.itemName, item);
              });
              items.add(section + 'Separator', Separator.component());
            }
          });

          return items;
        },
        userControls: function userControls(post, context) {
          return new ItemList();
        },
        moderationControls: function moderationControls(post, context) {
          var items = new ItemList();

          if (post.contentType() === 'comment' && post.canEdit()) {
            if (!post.isHidden()) {
              items.add('edit', Button.component({
                icon: 'pencil',
                children: app.translator.trans('core.forum.post_controls.edit_button'),
                onclick: this.editAction.bind(post)
              }));
            }
          }

          return items;
        },
        destructiveControls: function destructiveControls(post, context) {
          var items = new ItemList();

          if (post.contentType() === 'comment' && !post.isHidden()) {
            if (post.canEdit()) {
              items.add('hide', Button.component({
                icon: 'trash-o',
                children: app.translator.trans('core.forum.post_controls.delete_button'),
                onclick: this.hideAction.bind(post)
              }));
            }
          } else {
            if (post.contentType() === 'comment' && post.canEdit()) {
              items.add('restore', Button.component({
                icon: 'reply',
                children: app.translator.trans('core.forum.post_controls.restore_button'),
                onclick: this.restoreAction.bind(post)
              }));
            }
            if (post.canDelete()) {
              items.add('delete', Button.component({
                icon: 'times',
                children: app.translator.trans('core.forum.post_controls.delete_forever_button'),
                onclick: this.deleteAction.bind(post, context)
              }));
            }
          }

          return items;
        },
        editAction: function editAction() {
          app.composer.load(new EditPostComposer({ post: this }));
          app.composer.show();
        },
        hideAction: function hideAction() {
          this.pushAttributes({ hideTime: new Date(), hideUser: app.session.user });

          return this.save({ isHidden: true }).then(function () {
            return m.redraw();
          });
        },
        restoreAction: function restoreAction() {
          this.pushAttributes({ hideTime: null, hideUser: null });

          return this.save({ isHidden: false }).then(function () {
            return m.redraw();
          });
        },
        deleteAction: function deleteAction(context) {
          var _this2 = this;

          if (context) context.loading = true;

          return this.delete().then(function () {
            var discussion = _this2.discussion();

            discussion.removePost(_this2.id());

            // If this was the last post in the discussion, then we will assume that
            // the whole discussion was deleted too.
            if (!discussion.postIds().length) {
              // If there is a discussion list in the cache, remove this discussion.
              if (app.cache.discussionList) {
                app.cache.discussionList.removeDiscussion(discussion);
              }

              if (app.viewingDiscussion(discussion)) {
                app.history.back();
              }
            }
          }).catch(function () {}).then(function () {
            if (context) context.loading = false;
            m.redraw();
          });
        }
      });
    }
  };
});;
"use strict";

System.register("flarum/utils/RequestError", [], function (_export, _context) {
  "use strict";

  var RequestError;
  return {
    setters: [],
    execute: function () {
      RequestError = function RequestError(status, responseText, options, xhr) {
        babelHelpers.classCallCheck(this, RequestError);

        this.status = status;
        this.responseText = responseText;
        this.options = options;
        this.xhr = xhr;

        try {
          this.response = JSON.parse(responseText);
        } catch (e) {
          this.response = null;
        }

        this.alert = null;
      };

      _export("default", RequestError);
    }
  };
});;
"use strict";

System.register("flarum/utils/ScrollListener", [], function (_export, _context) {
  "use strict";

  var scroll, ScrollListener;
  return {
    setters: [],
    execute: function () {
      scroll = window.requestAnimationFrame || window.webkitRequestAnimationFrame || window.mozRequestAnimationFrame || window.msRequestAnimationFrame || window.oRequestAnimationFrame || function (callback) {
        return window.setTimeout(callback, 1000 / 60);
      };

      ScrollListener = function () {
        /**
         * @param {Function} callback The callback to run when the scroll position
         *     changes.
         * @public
         */
        function ScrollListener(callback) {
          babelHelpers.classCallCheck(this, ScrollListener);

          this.callback = callback;
          this.lastTop = -1;
        }

        /**
         * On each animation frame, as long as the listener is active, run the
         * `update` method.
         *
         * @protected
         */


        babelHelpers.createClass(ScrollListener, [{
          key: "loop",
          value: function loop() {
            if (!this.active) return;

            this.update();

            scroll(this.loop.bind(this));
          }
        }, {
          key: "update",
          value: function update(force) {
            var top = window.pageYOffset;

            if (this.lastTop !== top || force) {
              this.callback(top);
              this.lastTop = top;
            }
          }
        }, {
          key: "start",
          value: function start() {
            if (!this.active) {
              this.active = true;
              this.loop();
            }
          }
        }, {
          key: "stop",
          value: function stop() {
            this.active = false;
          }
        }]);
        return ScrollListener;
      }();

      _export("default", ScrollListener);
    }
  };
});;
'use strict';

System.register('flarum/utils/slidable', [], function (_export, _context) {
  "use strict";

  function slidable(element) {
    var $element = $(element);
    var threshold = 50;

    var $underneathLeft = void 0;
    var $underneathRight = void 0;

    var startX = void 0;
    var startY = void 0;
    var couldBeSliding = false;
    var isSliding = false;
    var pos = 0;

    /**
     * Animate the slider to a new position.
     *
     * @param {Integer} newPos
     * @param {Object} [options]
     */
    var animatePos = function animatePos(newPos) {
      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

      // Since we can't animate the transform property with jQuery, we'll use a
      // bit of a workaround. We set up the animation with a step function that
      // will set the transform property, but then we animate an unused property
      // (background-position-x) with jQuery.
      options.duration = options.duration || 'fast';
      options.step = function (x) {
        $(this).css('transform', 'translate(' + x + 'px, 0)');
      };

      $element.find('.Slidable-content').animate({ 'background-position-x': newPos }, options);
    };

    /**
     * Revert the slider to its original position.
     */
    var reset = function reset() {
      animatePos(0, {
        complete: function complete() {
          $element.removeClass('sliding');
          $underneathLeft.hide();
          $underneathRight.hide();
          isSliding = false;
        }
      });
    };

    $element.find('.Slidable-content').on('touchstart', function (e) {
      // Update the references to the elements underneath the slider, provided
      // they're not disabled.
      $underneathLeft = $element.find('.Slidable-underneath--left:not(.disabled)');
      $underneathRight = $element.find('.Slidable-underneath--right:not(.disabled)');

      startX = e.originalEvent.targetTouches[0].clientX;
      startY = e.originalEvent.targetTouches[0].clientY;

      couldBeSliding = true;
      pos = 0;
    }).on('touchmove', function (e) {
      var newX = e.originalEvent.targetTouches[0].clientX;
      var newY = e.originalEvent.targetTouches[0].clientY;

      // Once the user moves their touch in a direction that's more up/down than
      // left/right, we'll assume they're scrolling the page. But if they do
      // move in a horizontal direction at first, then we'll lock their touch
      // into the slider.
      if (couldBeSliding && Math.abs(newX - startX) > Math.abs(newY - startY)) {
        isSliding = true;
      }
      couldBeSliding = false;

      if (isSliding) {
        pos = newX - startX;

        // If there are controls underneath the either side, then we'll show/hide
        // them depending on the slider's position. We also make the controls
        // icon get a bit bigger the further they slide.
        var toggle = function toggle($underneath, side) {
          if ($underneath.length) {
            var active = side === 'left' ? pos > 0 : pos < 0;

            if (active && $underneath.hasClass('Slidable-underneath--elastic')) {
              pos -= pos * 0.5;
            }
            $underneath.toggle(active);

            var scale = Math.max(0, Math.min(1, (Math.abs(pos) - 25) / threshold));
            $underneath.find('.icon').css('transform', 'scale(' + scale + ')');
          } else {
            pos = Math[side === 'left' ? 'min' : 'max'](0, pos);
          }
        };

        toggle($underneathLeft, 'left');
        toggle($underneathRight, 'right');

        $(this).css('transform', 'translate(' + pos + 'px, 0)');
        $(this).css('background-position-x', pos + 'px');

        $element.toggleClass('sliding', !!pos);

        e.preventDefault();
      }
    }).on('touchend', function () {
      // If the user releases the touch and the slider is past the threshold
      // position on either side, then we will activate the control for that
      // side. We will also animate the slider's position all the way to the
      // other side, or back to its original position, depending on whether or
      // not the side is 'elastic'.
      var activate = function activate($underneath) {
        $underneath.click();

        if ($underneath.hasClass('Slidable-underneath--elastic')) {
          reset();
        } else {
          animatePos((pos > 0 ? 1 : -1) * $element.width());
        }
      };

      if ($underneathRight.length && pos < -threshold) {
        activate($underneathRight);
      } else if ($underneathLeft.length && pos > threshold) {
        activate($underneathLeft);
      } else {
        reset();
      }

      couldBeSliding = false;
      isSliding = false;
    });

    return { reset: reset };
  }
  _export('default', slidable);

  return {
    setters: [],
    execute: function () {
      ; /**
         * The `slidable` utility adds touch gestures to an element so that it can be
         * slid away to reveal controls underneath, and then released to activate those
         * controls.
         *
         * It relies on the element having children with particular CSS classes.
         * TODO: document
         *
         * @param {DOMElement} element
         * @return {Object}
         * @property {function} reset Revert the slider to its original position. This
         *     should be called, for example, when a controls dropdown is closed.
         */
    }
  };
});;
'use strict';

System.register('flarum/utils/string', [], function (_export, _context) {
  "use strict";

  /**
   * Truncate a string to the given length, appending ellipses if necessary.
   *
   * @param {String} string
   * @param {Number} length
   * @param {Number} [start=0]
   * @return {String}
   */
  function truncate(string, length) {
    var start = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 0;

    return (start > 0 ? '...' : '') + string.substring(start, start + length) + (string.length > start + length ? '...' : '');
  }

  /**
   * Create a slug out of the given string. Non-alphanumeric characters are
   * converted to hyphens.
   *
   * @param {String} string
   * @return {String}
   */

  _export('truncate', truncate);

  function slug(string) {
    return string.toLowerCase().replace(/[^a-z0-9]/gi, '-').replace(/-+/g, '-').replace(/-$|^-/g, '') || '-';
  }

  /**
   * Strip HTML tags and quotes out of the given string, replacing them with
   * meaningful punctuation.
   *
   * @param {String} string
   * @return {String}
   */

  _export('slug', slug);

  function getPlainContent(string) {
    var html = string.replace(/(<\/p>|<br>)/g, '$1 &nbsp;').replace(/<img\b[^>]*>/ig, ' ');

    var dom = $('<div/>').html(html);

    dom.find(getPlainContent.removeSelectors.join(',')).remove();

    return dom.text().replace(/\s+/g, ' ').trim();
  }

  /**
   * An array of DOM selectors to remove when getting plain content.
   *
   * @type {Array}
   */

  _export('getPlainContent', getPlainContent);

  /**
   * Make a string's first character uppercase.
   *
   * @param {String} string
   * @return {String}
   */
  function ucfirst(string) {
    return string.substr(0, 1).toUpperCase() + string.substr(1);
  }

  _export('ucfirst', ucfirst);

  return {
    setters: [],
    execute: function () {
      getPlainContent.removeSelectors = ['blockquote', 'script'];
    }
  };
});;
'use strict';

System.register('flarum/utils/stringToColor', [], function (_export, _context) {
  "use strict";

  function hsvToRgb(h, s, v) {
    var r = void 0;
    var g = void 0;
    var b = void 0;

    var i = Math.floor(h * 6);
    var f = h * 6 - i;
    var p = v * (1 - s);
    var q = v * (1 - f * s);
    var t = v * (1 - (1 - f) * s);

    switch (i % 6) {
      case 0:
        r = v;g = t;b = p;break;
      case 1:
        r = q;g = v;b = p;break;
      case 2:
        r = p;g = v;b = t;break;
      case 3:
        r = p;g = q;b = v;break;
      case 4:
        r = t;g = p;b = v;break;
      case 5:
        r = v;g = p;b = q;break;
    }

    return {
      r: Math.floor(r * 255),
      g: Math.floor(g * 255),
      b: Math.floor(b * 255)
    };
  }

  /**
   * Convert the given string to a unique color.
   *
   * @param {String} string
   * @return {String}
   */
  function stringToColor(string) {
    var num = 0;

    // Convert the username into a number based on the ASCII value of each
    // character.
    for (var i = 0; i < string.length; i++) {
      num += string.charCodeAt(i);
    }

    // Construct a color using the remainder of that number divided by 360, and
    // some predefined saturation and value values.
    var hue = num % 360;
    var rgb = hsvToRgb(hue / 360, 0.3, 0.9);

    return '' + rgb.r.toString(16) + rgb.g.toString(16) + rgb.b.toString(16);
  }

  _export('default', stringToColor);

  return {
    setters: [],
    execute: function () {}
  };
});;
'use strict';

System.register('flarum/utils/SubtreeRetainer', [], function (_export, _context) {
  "use strict";

  var SubtreeRetainer;
  return {
    setters: [],
    execute: function () {
      SubtreeRetainer = function () {
        /**
         * @param {...callbacks} callbacks Functions returning data to keep track of.
         */
        function SubtreeRetainer() {
          babelHelpers.classCallCheck(this, SubtreeRetainer);

          for (var _len = arguments.length, callbacks = Array(_len), _key = 0; _key < _len; _key++) {
            callbacks[_key] = arguments[_key];
          }

          this.callbacks = callbacks;
          this.data = {};
        }

        /**
         * Return a virtual DOM directive that will retain a subtree if no data has
         * changed since the last check.
         *
         * @return {Object|false}
         * @public
         */


        babelHelpers.createClass(SubtreeRetainer, [{
          key: 'retain',
          value: function retain() {
            var _this = this;

            var needsRebuild = false;

            this.callbacks.forEach(function (callback, i) {
              var result = callback();

              if (result !== _this.data[i]) {
                _this.data[i] = result;
                needsRebuild = true;
              }
            });

            return needsRebuild ? false : { subtree: 'retain' };
          }
        }, {
          key: 'check',
          value: function check() {
            for (var _len2 = arguments.length, callbacks = Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
              callbacks[_key2] = arguments[_key2];
            }

            this.callbacks = this.callbacks.concat(callbacks);
          }
        }, {
          key: 'invalidate',
          value: function invalidate() {
            this.data = {};
          }
        }]);
        return SubtreeRetainer;
      }();

      _export('default', SubtreeRetainer);
    }
  };
});;
'use strict';

System.register('flarum/utils/UserControls', ['flarum/components/Button', 'flarum/components/Separator', 'flarum/components/EditUserModal', 'flarum/components/UserPage', 'flarum/utils/ItemList'], function (_export, _context) {
  "use strict";

  var Button, Separator, EditUserModal, UserPage, ItemList;
  return {
    setters: [function (_flarumComponentsButton) {
      Button = _flarumComponentsButton.default;
    }, function (_flarumComponentsSeparator) {
      Separator = _flarumComponentsSeparator.default;
    }, function (_flarumComponentsEditUserModal) {
      EditUserModal = _flarumComponentsEditUserModal.default;
    }, function (_flarumComponentsUserPage) {
      UserPage = _flarumComponentsUserPage.default;
    }, function (_flarumUtilsItemList) {
      ItemList = _flarumUtilsItemList.default;
    }],
    execute: function () {
      _export('default', {
        controls: function controls(discussion, context) {
          var _this = this;

          var items = new ItemList();

          ['user', 'moderation', 'destructive'].forEach(function (section) {
            var controls = _this[section + 'Controls'](discussion, context).toArray();
            if (controls.length) {
              controls.forEach(function (item) {
                return items.add(item.itemName, item);
              });
              items.add(section + 'Separator', Separator.component());
            }
          });

          return items;
        },
        userControls: function userControls() {
          return new ItemList();
        },
        moderationControls: function moderationControls(user) {
          var items = new ItemList();

          if (user.canEdit()) {
            items.add('edit', Button.component({
              icon: 'pencil',
              children: app.translator.trans('core.forum.user_controls.edit_button'),
              onclick: this.editAction.bind(user)
            }));
          }

          return items;
        },
        destructiveControls: function destructiveControls(user) {
          var items = new ItemList();

          if (user.id() !== '1' && user.canDelete()) {
            items.add('delete', Button.component({
              icon: 'times',
              children: app.translator.trans('core.forum.user_controls.delete_button'),
              onclick: this.deleteAction.bind(user)
            }));
          }

          return items;
        },
        deleteAction: function deleteAction() {
          var _this2 = this;

          if (confirm(app.translator.trans('core.forum.user_controls.delete_confirmation'))) {
            this.delete().then(function () {
              if (app.current instanceof UserPage && app.current.user === _this2) {
                app.history.back();
              } else {
                window.location.reload();
              }
            });
          }
        },
        editAction: function editAction() {
          app.modal.show(new EditUserModal({ user: this }));
        }
      });
    }
  };
});