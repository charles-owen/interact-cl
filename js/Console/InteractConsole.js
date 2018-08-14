/**
 * @file
 * Install into the Interact console system.
 */

export const InteractConsole = function() {
}

InteractConsole.install = function(Console) {

    Console.tables.add({
        title: 'Interact',
        order: 12,
        api: '/api/interact/tables'
    });
}

