/**
 * @file
 */

import {Interact} from './Interact';
import {InteractView} from './InteractView';

export const InteractFactory = function() {
}

InteractFactory.create = function(site) {

    const interact = new Interact();

    site.ready(() => {
        for(let element of document.querySelectorAll('div.cl-interact')) {
            new InteractView(site, element);
        }
    });

    return interact;
}