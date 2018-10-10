import {Interact} from './Interact';
import {InteractView} from './InteractView';
import {InteractPageView} from './InteractPageView';
import {PageVue} from "site-cl/js/Vue/PageVue";
import VinnieVue from './Autoanswer/Vinnie.vue';

/**
 * Factory class to create Interact object and install Interact system
 * @constructor
 */
export const InteractFactory = function() {
}

/**
 * Create the Interact object and install Interact system
 * @param {Site} site
 * @returns {Interact}
 */
InteractFactory.create = function(site) {

    const interact = new Interact(site);

	//
	// This Vue mixin will allow the Interact object to be available
	// as $interact Vue objects
	//
	site.Vue.mixin( {
		beforeCreate() {
			const options = this.$options;
			if ( options.interact )
				this.$interact= options.interact;
			else if ( options.parent && options.parent.$interact )
				this.$interact = options.parent.$interact;
		}
	} );

    site.ready(() => {
        let element = document.getElementById('cl-interact');
        if(element !== null) {
            new InteractView(site, interact, element);
        }

        element = document.getElementById('cl-interact-page');
        if(element !== null) {
        	new InteractPageView(site, interact, element);
        }

        PageVue.create('div.cl-vinnie-vue', 'Vinnie Vue', VinnieVue);
    });

    return interact;
}