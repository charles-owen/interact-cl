import './_interact.scss';
import {InteractFactory} from './js/InteractFactory';
import {InteractConsole} from './js/Console/InteractConsole';

if(!Site.Interact) {
    Site.Interact = InteractFactory.create(Site);

    if(Site.Site.console !== undefined) {
        InteractConsole.install(Site.Console);
    }
}


