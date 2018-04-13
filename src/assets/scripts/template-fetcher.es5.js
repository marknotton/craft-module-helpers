'use strict';

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var TemplateFetcher = function () {
  function TemplateFetcher() {
    _classCallCheck(this, TemplateFetcher);

    var $this = this;

    // Default settings
    this.settings = {
      action: '/fetch-template',
      fetch: true,
      csrf: {
        name: window.csrfTokenName !== undefined ? window.csrfTokenName : 'X-CSRF-Token',
        token: window.csrfTokenValue !== undefined ? window.csrfTokenValue : null
      },
      dev: false // Shows console logs


      // Manage settings
    };
    for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    if (args.length) {
      args.forEach(function (setting) {
        switch (typeof setting === 'undefined' ? 'undefined' : _typeof(setting)) {
          case 'boolean':
            $this.settings.fetch = setting;
            break;
          case 'string':
            $this.settings.action = setting;
            break;
          case 'object':
            // $this.settings = Object.assign({}, $this.settings, setting)
            for (var attrname in setting) {
              $this.settings[attrname] = setting[attrname];
            }
            break;
        }
      });
    }

    if (this.settings.dev) {
      console.log(this.settings);
    }
  }

  _createClass(TemplateFetcher, [{
    key: 'template',
    value: function template(args, callback) {
      if (args !== undefined && callback !== undefined) {
        if (this.settings.fetch) {
          this.fetcher.fetchMethod(this, args, callback);
        } else {
          this.fetcher.ajaxMethod(this, args, callback);
        }
      }
    }
  }, {
    key: 'fetcher',
    get: function get() {
      return {

        // Menu button event handler
        ajaxMethod: function ajaxMethod($this, args, callback) {
          // AJAX (ES5 + jQuery)
          $.ajax({
            type: 'POST',
            dataType: 'json',
            url: $this.settings.action,
            data: args,
            headers: _defineProperty({}, $this.settings.csrf.name, $this.settings.csrf.token),
            success: function success(data) {
              if (data.success === true) {
                callback(data);
                if ($this.settings.dev) {
                  console.log('Success:', data);
                }
              } else {
                console.error('Error:', data);
              }
            },
            error: function error(data) {
              console.error('Template Error:', data.responseJSON.error);
            }
          });
        },


        // Fetch (ES6)
        fetchMethod: function fetchMethod($this, args, callback) {
          window.fetch($this.settings.action, {
            mode: 'cors',
            method: 'POST',
            headers: new Headers(_defineProperty({
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Requested-With': 'fetch'
            }, $this.settings.csrf.name, $this.settings.csrf.token)),
            body: JSON.stringify(args),
            credentials: 'same-origin'
          }).then(function (response) {
            return response.json().then(function (data) {
              if (response.ok) {
                if ($this.settings.dev) {
                  console.log('Success:', data);
                }
                callback(data);
                return data;
              } else {
                console.error('Error:', data.error);
                return Promise.reject({ status: response.status, data: data });
              }
            });
          }).catch(function (error) {
            return console.error('Error:', error);
          });
        }
      };
    }
  }]);

  return TemplateFetcher;
}();
