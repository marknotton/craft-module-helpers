class TemplateFetcher {
  constructor (...args) {
    const $this = this

    // Default settings
    this.settings = {
      action: '/fetch-template',
      fetch: true,
      csrf: {
        name: window.csrfTokenName !== undefined ? window.csrfTokenName : 'X-CSRF-Token',
        token: window.csrfTokenValue !== undefined ? window.csrfTokenValue : null
      },
      dev: false // Shows console logs
    }

    // Manage settings
    if (args.length) {
      args.forEach(function (setting) {
        switch (typeof (setting)) {
          case 'boolean':
            $this.settings.fetch = setting
            break
          case 'string':
            $this.settings.action = setting
            break
          case 'object':
            // $this.settings = Object.assign({}, $this.settings, setting)
            for (var attrname in setting) {
              $this.settings[attrname] = setting[attrname];
            }
            break
        }
      })
    }

    if (this.settings.dev) {
      console.log(this.settings)
    }
  }

  get fetcher () {
    return {

      // Menu button event handler
      ajaxMethod ($this, args, callback) {
        // AJAX (ES5 + jQuery)
        $.ajax({
          type : 'POST',
          dataType: 'json',
          url: $this.settings.action,
          data: args,
          headers: {
            [$this.settings.csrf.name]: $this.settings.csrf.token
          },
          success (data) {
            if (data.success === true) {
              callback(data)
              if ($this.settings.dev) {
                console.log('Success:', data)
              }
            } else {
              console.error('Error:', data)
            }
          },
          error (data) {
            console.error('Template Error:', data.responseJSON.error)
          }
        })
      },

      // Fetch (ES6)
      fetchMethod ($this, args, callback) {
        window.fetch($this.settings.action, {
          mode: 'cors',
          method: 'POST',
          headers: new Headers({
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'fetch',
            [$this.settings.csrf.name]: $this.settings.csrf.token
          }),
          body: JSON.stringify(args),
          credentials: 'same-origin'
        })
          .then(response => {
            return response.json().then(data => {
              if (response.ok) {
                if ($this.settings.dev) {
                  console.log('Success:', data)
                }
                callback(data)
                return data
              } else {
                console.error('Error:', data.error)
                return Promise.reject({status: response.status, data})
              }
            })
          })
          .catch(error => console.error('Error:', error))
      }
    }
  }

  template (args, callback) {
    if (args !== undefined && callback !== undefined) {
      if (this.settings.fetch) {
        this.fetcher.fetchMethod(this, args, callback)
      } else {
        this.fetcher.ajaxMethod(this, args, callback)
      }
    }
  }
}
