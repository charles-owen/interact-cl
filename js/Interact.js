/**
 * Interact central (system) object
 * @param {Site} site The Site object
 * @constructor
 */
export const Interact = function(site) {


	this.startPolling = function() {
		console.log('startPolling');

		site.polling.addClient('interact', (params) => {
			params.interact = {};

			if(this.summaries !== null) {
				this.summaries.prePolling(params.interact);
			}

			//console.log(params);
		}, (response) => {
			if(this.summaries !== null) {
				this.summaries.postPolling(response);
			}
		});
	}

	this.endPolling = function() {
		console.log('endPolling');
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
}
