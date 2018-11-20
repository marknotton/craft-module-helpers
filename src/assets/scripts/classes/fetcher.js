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
      dev: false // Shows console logs
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

  // AJAX (ES5 + jQuery) =======================================================

	ajaxMethod (data, callback, settings = this.settings) {
    $.ajax({
      type     : 'POST',
      dataType : settings.dataType,
      url      : settings.url,
      data     : data,
      headers  : {
        [settings.csrf.name]: settings.csrf.token
      },
      success (data) {
        if (data.success === true) {
          callback(data)
          if (settings.dev) {
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
  }

  // Fetch (ES6) ===============================================================

  fetchMethod (data, callback, settings = this.settings) {
    fetch(settings.url, {
      mode    : 'cors',
      method  : 'POST',
      headers : new Headers({
        'Content-Type'     : 'application/json',
        'Accept'           : 'application/json',
        'X-Requested-With' : 'fetch',
        [settings.csrf.name]: settings.csrf.token
      }),
      body: JSON.stringify(data),
      credentials: 'same-origin',
    })
    .then(response => {
      return response[settings.dataType]().then(data => {
        if (response.ok && !data.error) {
          if (settings.dev) {
            console.log('Success:', data)
          }
          if ( callback ) { callback(data); }
          return data
        } else {
          return Promise.reject({status: response.status, data})
        }
      })
    })
    .catch(error => {
      if ( error ) {
        if ( callback ) { callback(error.data); }
        console.error('Error:', error.data)
      } else {
        console.error('Unknown error')
      }
    })
  }

	// Template ==================================================================

  template (...args) {

		if (!args) {
			console.warn('No arguments were passed');
			return false;
		}

		let data = null;
		let settings = Object.assign({}, this.settings);
		let callback = null;

		args.forEach(arg => {
			switch(typeof arg) {
			  case 'function':
			    callback = arg;
			  break;
			  case 'object':
			    if ( !data ) {
						data = arg;
					} else {
						// TODO: apply deep merge where keys are replaced, not added.
						settings = Object.assign({}, settings, arg)
					}
			  break;
				case 'string':
					if ( !data ) {
						data = { section : arg };
					} else {
						settings['url'] = arg
					}
				break;
			}
		})

		if (settings.fetch) {
      return this.fetchMethod(data, callback, settings)
    } else {
      return this.ajaxMethod(data, callback, settings)
    }
  }

	// Alias for template function ===============================================

  get (...args) {

		// TODO: Set a default url path if one isn't added as a string in the second param,
		// or if one exists in the second param object. Look for url.

    this.template(...args)
  }
};
