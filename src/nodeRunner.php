#!/usr/bin/env php
<?php declare(strict_types=1);

/* {{{ */
/**
 * runner/manager for the 'cluster-queue' project
 * ----------------------------------------------------------------------------
 * @license LGPL Version 3 http://www.gnu.org/licenses/lgpl-3.0.txt
 * @copyright Copyright (c) 2020 by Florian Blasel
 * @author Florian Blasel <flobee.code@gmail.com>
 * ----------------------------------------------------------------------------
 * @category    cli
 * @package     cluster-queue
 * @subpackage  core
 * Created: 2020-11-07
 */
/* }}} */

/**
 * php cluster queue manager.
 *
 * - Scann for server configs,
 * - replace params and
 * - create a concrete config structure for a configured node
 * for a specific node
 * - deploy build configs and configs from skel/
 * - archive stuff we may need later or for backup
 * - execute commands to install, manage, maintain a node
 * - in specific order
 *
 * Requirements:
 * - Mode: Admins
 * - root access required
 * - pub key auth must be possible at the nodes
 */

//
// pre check
//

if ( PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' ) {
    $mesg = 'Warning: This program should be called in the CLI version of PHP! '
        . 'Not within the ' . PHP_SAPI . ' SAPI' . PHP_EOL;
    exit( $mesg );
}

//
// boot
//

$inputCnt = $_SERVER['argc'];
$shortopts = '';
$longopts = array(
    // Configuration suffix to load eg: debnode configs:
    // - loads 'nodeconfigs_debnode.php' 'nodeconfigs_debnode_default.php'
    "config:", // Required value
    // action to perform
    "action:", // Required value
    // Optional flag for a REAL execution! **Danger**! Without: It gives you a job list.
    "execute",
    // Flag to confirm each execution (default: disabled), if --execute set
    "confirm",
    // Optional flag to reduce shell output to a minimum (logging always enabled)
    "no-debug",
);
$actions = array(
    // handle all in default config exists for _ALL_ nodes (actions, deploy, archive).
    'defaults',
    // creates/ builds config/script files to be deployed or executed to/for the target
    'create','build',
    // node configs which may need the create/build part.
    'configs',
    //
    // -- custom ---
    //
    // outputs ONLY deploy actions to deploy to the target
    'deploy',
    // outputs ONLY archive actions to archive from the target
    'archive',
    // output ONLY actions to execute at the target to setup/configure a node/ server/ service details
    'actions'
);
$input = getopt( $shortopts, $longopts );

$usageMesg = 'Usage: ./php-cluster-queue [--config:<nodes config>] [--action: ' . implode( '|', $actions ) . ']'
    . PHP_EOL . 'eg: ./php-cluster-queue --config=debnode --action=create' . PHP_EOL;
if ( $inputCnt < 3 ) {
    exit( $usageMesg );
}

//
// check/ validate input:
//

try {
    if ( isset($input['config']) && ctype_alpha( $input['config'] ) ) {
        $configNodesLoc = 'src/config/nodeconfigs_' . $input['config'] . '.php';
        if ( !file_exists( $configNodesLoc ) ) {
            throw new Exception( 'Invalid config location: "' . $configNodesLoc . '"' );
        } else {
            $configNodes = require_once $configNodesLoc;
        }

        $configsDefaultLoc = 'src/config/nodeconfigs_' . $input['config'] . '_default.php';
        if ( !file_exists( $configsDefaultLoc ) ) {
            // no but, but to report
            echo 'Invalid default config location: "' . $configsDefaultLoc . '"' . PHP_EOL;
            $configsDefault = array();
        } else {
            $configsDefault = require_once $configsDefaultLoc;
        }
    } else {
        throw new Exception( 'Invalid/ missing config value' );
    }

    if ( !isset($input['action']) || !in_array( $input['action'], $actions ) ) {
        throw new Exception( 'Invalid action value ( ' . implode( ' | ', $actions ) . ' )' );
    }
}
catch ( Exception $ex ) {
    echo $ex->getMessage() . PHP_EOL;
    echo $usageMesg;
    exit( 1 );
}

//
// run
//

require_once 'vendor/autoload.php';

$logOptions = array(
    'logfile' => './logs/'. $input['config'] .'.log',
    'way' => 'a',
    'logLevel' => 7,
    'lineFormat'=>'%5$s',
    'maxfilesize' => (1024 * 1024 * 1), // 1M
);

$logger = new Mumsys_Logger_File( $logOptions );

/** @var QueueHelper Helper/manager object */
$runner = new QueueHelper( $input, $configNodes, $configsDefault, $logger );

switch ( $input['action'] ) {
    case 'create':
    case 'build':
        $runner->configsCreate();
        break;

    case 'defaults':
        $runner->queueDefaults();

        $runner->buildQueue();

        $runner->run();
        break;

    case 'configs':
        $runner->configsCreate();
        $runner->queueConfigs();

        $runner->buildQueue();

        $runner->run();
        break;

    // --- custom ---

    // use only all deploy actions
    // split by type vs queue dependencies: difficult! this only outputs deploy tasks
    case 'deploy':
        $runner->configsCreate();

        $runner->queueDefaults();
        $runner->queueConfigs();

        $runner->buildQueue();

        $runner->run('deploy');
        break;

    case 'archive':
        $runner->configsCreate();

        $runner->queueDefaults();
        $runner->queueConfigs();

        $runner->buildQueue();

        $runner->run('archive');
        break;

    case 'actions':
    case 'excecutions':
        $runner->configsCreate();

        $runner->queueDefaults();
        $runner->queueConfigs();

        $runner->buildQueue();

        $runner->run('execute');
        break;

    // may does not make sence because of the defaults for all nodes (if there are
    // dependencies between some nodes)
//    case 'all':
//        $runner->configsCreate();
//
//        $runner->queueDefaults();
//        $runner->queueConfigs();
//
//        $runner->buildQueue();
//
//        $runner->run();
//        break;
}
