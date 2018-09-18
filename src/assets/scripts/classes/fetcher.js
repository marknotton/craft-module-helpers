class TemplateFetcher {

  constructor (...args) {
    // Default settings
    this.settings = {
      url      : '/fetch-template',
      fetch    : 'fetch' in window,
      dataType : 'json',
      csrf     : {
        name  : window.csrfTokenName !== undefined ? window.csrfTokenName : 'X-CSRF-Token',
        token : window.csrfTokenValue !== undefined ? window.csrfTokenValue : null
      },
      dev: true // Shows console logs
    }

    // Manage settings
    if (args.length) {
      args.forEach((setting) => {
        switch (typeof (setting)) {
          case 'boolean':
            this.settings.fetch = setting
            break
          case 'string':
            this.settings.url = setting
            break
          case 'object':
            // this.settings = Object.assign({}, this.settings, setting)
            for (var attrname in setting) {
              this.settings[attrname] = setting[attrname];
            }
            break
        }
      })
    }

    // Add an abort constroller for manual fetch cancellations
    if (this.settings.fetch) {
      this.controller = new AbortController();
      this.signal = this.controller.signal;
    }

    if (this.settings.dev) {
      console.log(this.settings)
    }
  }

  abort() {
    if (this.settings.fetch) {
      this.controller.abort();
      if (this.settings.dev) {
        console.log('Fetch aborted');
      }
    }
  }

  get fetcher () {
    return {

      // AJAX (ES5 + jQuery)
      ajaxMethod ($this, data, callback) {
        $.ajax({
          type     : 'POST',
          dataType : $this.settings.dataType,
          url      : $this.settings.url,
          data     : data,
          headers  : {
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
            return data;
          },
          error (data) {
            console.error('Template Error:', data.responseJSON.error)
          }
        })
      },

      // Fetch (ES6)
      fetchMethod ($this, data, callback) {
        fetch($this.settings.url, {
          mode    : 'cors',
          method  : 'POST',
          headers : new Headers({
            'Content-Type'     : 'application/json',
            'Accept'           : 'application/json',
            'X-Requested-With' : 'fetch',
            [$this.settings.csrf.name]: $this.settings.csrf.token
          }),
          body: JSON.stringify(data),
          credentials: 'same-origin',
        })
        .then(response => {
          return response[$this.settings.dataType]().then(data => {
            if (response.ok && !data.error) {
              if ($this.settings.dev) {
                console.log('Success:', data)
              }
              if (callback ) { callback(data); }
              return data
            } else {
              return Promise.reject({status: response.status, data})
            }
          })
        })
        .catch(error => {
          if ( error ) {
            if (callback ) { callback(error.data); }
            console.error('Error:', error.data.message)
          } else {
            console.error('Unknown error')
          }
        })
      }
    }
  }

  template (data, callback) {
    if (this.settings.fetch) {
      this.fetcher.fetchMethod(this, data, callback)
    } else {
      this.fetcher.ajaxMethod(this, data, callback)
    }
  }

  get (url, dataType = 'json', data, callback) {
    this.settings.url = url;
    this.settings.dataType = dataType;
    this.template(data, callback);
  }
};
