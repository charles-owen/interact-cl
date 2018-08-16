/**
 * @file
 * Collection of InteractionSummary objects.
 */

import {InteractionSummary} from './InteractionSummary';

export const Summaries = function() {
    this.summaries = [];
    this.more = false;

    // Oldest time known, 0 if none
    this.oldestTime = 0;


    this.add = function(summary) {
        // Does this summary already exist?
        for(let i in this.summaries) {
            if(this.summaries[i].id === summary.id) {
                this.summaries.splice(i, 1);
                break;
            }
        }

        // Add it
        this.summaries.push(summary);

        // We keep track of the oldest non-pinned post we
        // see so we can get more posts later on.
        if(!summary.pin && (this.oldestTime === 0 || summary.time < this.oldestTime)) {
            this.oldestTime = summary.time;
        }

        // And sort
        this.summaries.sort((a, b) => {
            if(a.pin && !b.pin) {
                return -1;
            }

            if(!a.bin && b.pin) {
                return 1;
            }

            return b.time - a.time;
        });
    }

    this.fetch = function() {
        let query = {};

        if(this.oldestTime !== 0) {
            query.before = this.oldestTime;
        }

        this.more = false;
        Site.api.get('/api/interact/summaries', query)
            .then((response) => {
                if (!response.hasError()) {
                    const summaries = response.getData('interact-summaries');
                    if(summaries !== null) {
                        console.log(summaries);
                        for(let summary of summaries.attributes) {
                            if(summary.more === true) {
                                this.more = true;
                                break;
                            }

                            this.add(new InteractionSummary(summary));
                        }
                    }
                } else {
                    Site.toast(this, response);
                }

            })
            .catch((error) => {
                Site.toast(this, error);
            });
    }
}
