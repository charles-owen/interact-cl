/**
 * Interact central (system) object
 * @param {Site} site The Site object
 * @constructor
 */
export const Interact = function(site) {
	// Interact ID we are actively editing the discussion for
	let active = null;

	/**
	 * Start polling for Interact
	 * Installs pre and post polling handlers
	 */
	this.startPolling = function() {
		site.polling.addClient('interact', (params) => {
			params.interact = {
				instance: this.instance
			};

			if(active !== null) {
				params.interact.active = active;
			}

			if(this.summaries !== null) {
				this.summaries.prePolling(params.interact);
			}

			if(this.presenter !== null) {
				this.presenter.prePolling(params.interact);
			}
		}, (response) => {
			if(this.summaries !== null) {
				this.summaries.postPolling(response);
			}

			if(this.presenter !== null) {
				this.presenter.postPolling(response);
			}
		});
	}

	this.endPolling = function() {
		site.polling.removeClient('interact');
	}

	this.setActive = function(_active) {
		if(active !== _active) {
			site.api.post('/api/interact/active/' + this.instance, {active: _active})
			    .then((response) => {
			    })
			    .catch((error) => {
			    });
		}
		active = _active;
	}

	let random = (len) => {
		var text = "";
		var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

		for (var i = 0; i < len; i++)
			text += possible.charAt(Math.floor(Math.random() * possible.length));

		return text;
	}

	/**
	 * A random string that identifies the Interact instance
	 * for the server.
	 * @type {string}
	 */
	this.instance = random(32);

	/**
	 * The summaries collection
	 * @type {Summaries}
	 */
	this.summaries = null;

	/**
	 * Attached presenter vue
	 * @type {InteractionPresenterVue}
	 */
	this.presenter = null;
}
