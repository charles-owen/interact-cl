/**
 * @file
 * Object for each item on the left side (iteractions/item listing)
 */

/**
 * Object for each item on the left side (iteractions/item listing)
 * @param data Data object for the Interact as received from the server
 * @constructor
 */
export const InteractItem = function(data) {
    const id = data.id;
    this.id = id;
    this.pin = data.pin;
    this.time = data.time;
    this.type = data.type;
    this.summary = data.summary;
    this.summarized = data.summarized;      // Summary of message body
    this.discussions = data.discussions;
    this.attribution = data.attribution;
    this.closed = data.closed;
}

// /**
//  * Install the handler for clicking on the question for display
//  */
// InteractItem.prototype.attach = function() {
//     var id = this.id;
//     var that = this;
//     var q = "#q" + id + ",#q" + id + " a"
//     $(q).click(function(event) {
//         event.preventDefault();
//         event.stopPropagation();
//         that.parent.select(id);
//     });
// }
//
// /*
//  * Convert interaction to a left-side summary box.
//  */
// InteractItem.prototype.to_left_html = function(time) {
//     var html = '<div class="item';
//     if(this.discussions == 0 && !this.closed && this.type !== 'A') {
//         html += ' noresponse';
//     }
//     html += '" id="q' + this.id + '"><h3><a href="">';
//
//     if(this.pin) {
//         html += '<img src="' + this.parent.libroot + '/images/interact/pin25.png" alt="Pinned" width="17" height="25">';
//     }
//
//     if(this.type === 'A') {
//         html += '<img src="' + this.parent.libroot + '/images/interact/megaphone25.png" alt="Annoucement" width="29" height="26">';
//     }
//
//     var date = Interact.display_time(new Date(this.time * 1000), null, true);
//
//     html += ' ' + this.summary + '</a></h3>' +
//     '<p class="link">@' + this.id + ' ' + this.discussions + '<span>' + date + '</span></p>' +
//     '<p>' + this.summarized + '</p>' +
//     '<p class="tag">' + this.attribution + '</p></div>';
//
//     return html;
// }
//
