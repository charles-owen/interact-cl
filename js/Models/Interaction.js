

import {Discussion} from './Discussion';

/**
 * Model for an interaction in the system.
 *
 * Complete presentation as on the right hand side
 * @param {object} data Data from the server
 * @constructor
 */
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
    this.assign = data.assign;
    this.section = data.section;
	this.history = data.history;
	this.memberid = data.memberid;
	this.following = data.following;
	this.state = data.state;
	this.escalated = data.escalated;

    this.discussions = [];
    if(data.discussions !== undefined) {
        for(let discuss of data.discussions) {
            this.discussions.push(new Discussion(discuss));
        }
    }
}

/** User question expecting an answer */
Interaction.QUESTION = 'Q';

/** Announcement */
Interaction.ANNOUNCEMENT = 'A';

/** User is following the interaction */
Interaction.FOLLOWING = 'F';

/** User is not following */
Interaction.NOTFOLLOWING = 'N';

/** User will never follow */
Interaction.NEVERFOLLOW = 'X';

//
// Interaction states
//

Interaction.PENDING = 'P';    ///< Requiring an answer
Interaction.ANSWERED = 'A';   ///< Has been answered
Interaction.RESOLVED = 'R';   ///< Problem has been resolved
Interaction.CLOSED = 'C';     ///< Interaction is closed for further discussion
