import './_interact.scss';
import {InteractFactory} from './js/InteractFactory';
import {InteractConsole} from './js/Console/InteractConsole';

export const Interact = InteractFactory.create(Site.Site);

if(typeof Console !== 'undefined') {
    InteractConsole.install(Site.Console);
}

