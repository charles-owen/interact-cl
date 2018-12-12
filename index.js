import './_interact.scss';
import {InteractFactory} from './js/InteractFactory';
import {InteractConsole} from './js/Console/InteractConsole';

export const Interact = InteractFactory.create(Site.site);

if(Site.site.console !== undefined) {
    InteractConsole.install(Site.site.console);
}

