/**
 * @file
 * Model for an interaction in the system.
 *
 * Complete presentation as on the right hand side
 */

import {Discussion} from './Discussion';

export const Interaction = function(data) {
    this.id = data.id;
    this.pin = data.pin;
    this.private = data.private;
    this.created = data.created;
    this.time = data.time;
    this.type = data.type;
    this.summary = data.summary;
    this.summarized = data.summarized;      // Summary of message body
    this.discussionsCnt = data.discussionsCnt;
    this.attribution = data.attribution;
    this.closed = data.closed;
    this.message = data.message;
    this.by = data.by;
    this.byRole = data.byRole;

    this.discussions = [];
    if(data.discussions !== undefined) {
        for(let discuss of data.discussions) {
            this.discussions.push(new Discussion(discuss));
        }
    }
}

/// User question expecting an answer
Interaction.QUESTION = 'Q';

/// Announcement
Interaction.ANNOUNCEMENT = 'A';