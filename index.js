import './_interact.scss';
import {InteractFactory} from './js/InteractFactory';
import {InteractConsole} from './js/Console/InteractConsole';

export const Interact = InteractFactory.create(Site.Site);

if(Site.Site.console !== undefined) {
    InteractConsole.install(Site.Site.console);
}

