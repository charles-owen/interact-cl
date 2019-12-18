/*
 * Install into the console.
 */

import ControlPanelVue from './ControlPanel.vue';
import {Member} from 'course-cl/js/Members/Member'
import InteractStatisticsComponent from './InteractStatistics.vue';

/**
 * Installer for the Interact console components
 * @constructor
 */
export const InteractConsole = function() {
}

/**
 * Install the Interact console components
 * @param {Console} Console
 */
InteractConsole.install = function(Console) {

    const page = {title: 'Main', route: '', order: 1};

    Console.components.addOption({
        atLeast: Member.STAFF,
        page: page,
        section: {title: 'Interact', order: 6},
        order: 1,
        component: ControlPanelVue
    });

    Console.components.addRoute({route: '/interact/statistics', component: InteractStatisticsComponent});

    Console.tables.add({
        title: 'Interact',
        order: 50,
        api: '/api/interact/tables'
    });
}

