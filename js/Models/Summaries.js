
import {InteractionSummary} from './InteractionSummary';

/**
 * Collection of InteractionSummary objects.
 * @param data
 * @constructor
 */
export const Summaries = function(data) {
    this.summaries = [];
    this.more = false;

    /** Oldest time known, 0 if none */
    this.oldestTime = 0;

    /** Youngest time known, 0 if none */
    this.youngestTime = 0;


    this.add = function(summary) {
        // Does this summary already exist?
	    // If so, remove it, since the new version
	    // may be at a different place
	    this.remove(summary);

        // Add it if not deleted
	    if(summary.deleted !== true) {
		    this.summaries.push(summary);
	    }

        // We keep track of the oldest non-pinned post we
        // see so we can get more posts later on.
        if(!summary.pin && (this.oldestTime === 0 || summary.time < this.oldestTime)) {
            this.oldestTime = summary.time;
        }

        // Keep track of the youngest post we see so we can get
	    // more posts as they are created
	    if(this.youngestTime === 0 || summary.time > this.youngestTime) {
	    	this.youngestTime = summary.time;
	    }

        // And sort
        this.summaries.sort((a, b) => {
            if(a.pin && (!b.pin)) {
                return -1;
            }

            if((!a.pin) && b.pin) {
                return 1;
            }

            return b.time - a.time;
        });

    }

    this.remove = function(summary) {
	    // Does this summary exist?
	    for(let i in this.summaries) {
		    if(this.summaries[i].id === summary.id) {
			    this.summaries.splice(i, 1);
			    return true;
		    }
	    }

	    return false;
    }

    let makeQuery = () => {
	    let query = {};

	    if(data.categories.length === 1) {
		    query = {
			    assign: data.categories[0].tag,
			    section: data.section
		    };
	    }

	    return query;
    }

    this.fetch = function(fetched) {
        let query = makeQuery();

        if(this.oldestTime !== 0) {
            query.before = this.oldestTime;
        }

        this.more = false;
        Site.api.get('/api/interact/summaries', query)
            .then((response) => {
                if (!response.hasError()) {
                	this.take(response, fetched);
                } else {
                    Site.toast(this, response);
                }

            })
            .catch((error) => {
                Site.toast(this, error);
            });
    }

    this.take = function(response, fetched) {
	    const summaries = response.getData('interact-summaries');
	    if(summaries !== null) {
		    for(let summary of summaries.attributes) {
			    if(summary.more === true) {
				    this.more = true;
				    break;
			    }

			    this.add(new InteractionSummary(summary));
		    }

		    if(fetched !== undefined) {
		    	fetched();
		    }
	    }
	}

    this.get = function(id) {
	    // Does this summary exist?
	    for(let i in this.summaries) {
		    if(this.summaries[i].id === id) {
			    return this.summaries[i];
		    }
	    }

	    return null;
    }

    this.prePolling = function(params) {
	    let query = makeQuery();
	    if(this.youngestTime !== 0) {
	    	query.after = this.youngestTime;
	    }

    	params.summaries = query;
	}

	this.postPolling = function(response) {
    	this.take(response);
    	//console.log(response);
	}
}
