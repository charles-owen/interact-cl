/**
 * @file
 */

import {Interact} from './Interact';
import {InteractView} from './InteractView';
import {InteractPageView} from './InteractPageView';

export const InteractFactory = function() {
}

InteractFactory.create = function(site) {

    const interact = new Interact();

    site.ready(() => {
        let element = document.getElementById('cl-interact');
        if(element !== null) {
            new InteractView(site, element);
        }

        element = document.getElementById('cl-interact-page');
        if(element !== null) {
        	new InteractPageView(site, element);
        }
    });

    return interact;
}