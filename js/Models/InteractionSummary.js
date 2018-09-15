/*
 * Interaction summary for the left side (iteractions/item listing)
 */

/**
 * Object for each item on the left side (iteractions/item listing)
 * @param data Data object for the Interact as received from the server
 * @constructor
 */
export const InteractionSummary = function(data) {
    this.id = data.id;
    this.pin = data.pin;
    this.time = data.time;
    this.type = data.type;
    this.summary = data.summary;
    this.summarized = data.summarized;      // Summary of message body
    this.discussionsCnt = data.discussionsCnt;
    this.attribution = data.attribution;
    this.closed = data.closed;
    this.state = data.state;
    this.escalated = data.escalated;
}

