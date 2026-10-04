<?php

/**
 * Module migration dependency ordering.
 *
 * When installing or migrating modules, they must be run in this order
 * if multiple modules share a dependency chain.
 *
 * Format:  'ModuleName' => ['dependency1', 'dependency2']
 *
 * The orchestrator (module:migrate-ordered) reads this and ensures
 * dependencies are migrated before dependents.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Module Dependencies
    |--------------------------------------------------------------------------
    */

    'dependencies' => [

        // FSM stack — FSMCore MUST run first; all others depend on its tables
        // (fsm_stages, fsm_territories, fsm_locations, fsm_teams, fsm_tags,
        //  fsm_equipment, fsm_templates, fsm_orders)
        'FSMRecurring'         => ['FSMCore'],
        'FSMRoute'             => ['FSMCore'],
        'FSMVehicle'           => ['FSMCore'],
        'FSMServiceAgreement'  => ['FSMCore'],
        'FSMSkill'             => ['FSMCore'],
        'FSMActivity'          => ['FSMCore'],
        'FSMEquipment'         => ['FSMCore'],
        'FSMTimesheet'         => ['FSMCore'],
        'FSMAvailability'      => ['FSMCore'],
        'FSMStock'             => ['FSMCore'],
        'FSMCRM'               => ['FSMCore'],         // references fsm_locations, fsm_orders
        'FSMCalendar'          => ['FSMCore'],

        // Accounting stack
        'Accountings'          => ['Accounting'],

        // Titan stack
        'TitanDocs'            => ['TitanCore'],

        // QualityControl re-uses Inspection tables — Inspection must run first
        'QualityControl'       => ['Inspection'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Explicit migration order (top → bottom = first migrated)
    |--------------------------------------------------------------------------
    | Modules not listed here are migrated after all listed modules,
    | in alphabetical order.
    */

    'order' => [
        'Accounting',
        'Inspection',
        'FSMCore',
        'FSMRecurring',
        'FSMRoute',
        'FSMVehicle',
        'FSMServiceAgreement',
        'FSMSkill',
        'FSMActivity',
        'FSMEquipment',
        'FSMTimesheet',
        'FSMAvailability',
        'FSMStock',
        'FSMCRM',
        'FSMCalendar',
        'TitanCore',
        'TitanDocs',
        'QualityControl',
    ],

];
