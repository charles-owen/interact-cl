/**
 * @file
 * Discussion item
 */

export const Discussion = function(data) {
    console.log(data);
    this.id = data.id;
    this.time = data.time;
    this.message = data.message;
    this.by = data.by;
    this.byRole = data.byRole;
    this.interactId = data.interactId;
}
