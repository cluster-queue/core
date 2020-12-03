<?php // declare config

/**
 * pre reserved action keys:
 *  - < nodekey >:defaultdeploy
 *  - < nodekey >:defaultarchive
 *  - < nodekey >:defaultactions
 */

return array(
    // NODECURRENTPUB/ machine hostname to connect to
    // (nodekey = NODECURRENTPUB should be always the same)
    'localhost' => array(

        // text replacements/ substitution in config files for

        'replace' => array(

            // what ever you want to do with config templates put in here.

            'NODESHAREDIP' => '192.168.169.170', // HA IP for node01 or node02 for the activ one
            'NODESDOMAINNAME' => 'localhost', // or localhost.localdomain or NODECURRENTPUB (def)
            'NODECURRENTINT' => 'localhostint', // internal/ real hostname/ uname -n
            'NODECURRENTPUB' => 'localhost', // key of this config! + hostname to be known in dns/ outside
        ),

        // relevant files for the replacement for this node
        // starting in: './skel'
        'files' => array(
            //'/testfile.txt', // goes to root: /
            '/testfile.txt' => '/tmp/testfile_othername.txt',
        ),

        // Action configs/tokens to queue dependencies
        // Command types to execute: deploy, archive, execute
        // Note: software must be installed or an install task should be set before config
        // actions take affect.
        'actions' => array(
            //// job 1
            //    jobKey: nodekey:customID => array(
            //        type: deploy|archive|execute,
            //        value: cmd|src=>target|cmd=>array(opts)
            //        posway: before|after,
            //        poskey: nodekey:customID
            //        // default: 'posway' => 'after', 'poskey' => null,
            //    ),
            //...

            'localhost:deploybuilds' => array(
                'type' => 'deploy', 'value' => array(
                    // flag to include the values from 'files' from above ('replace' key)
                    // only once per node config!
                    'files' => true,
                ),
                'posway' => 'after',
                'poskey' => null,
            ),

            'localhost:deployfirst' => array(
                'type' => 'deploy', 'value' => array(
                    //'/NODECURRENTPUB/etc/mysql/mariadb.cnf' => '/etc/mysql/mariadb.cnf', // must exists in skel/
                    // 4 the active DB node (needs deploydefaults!)
                    //'/helper/phpmyadmin/phpmyadmin_create_tables.sql' => '/root/ha-inst-tmp/phpmyadmin_create_tables.sql',
                    //'/helper/phpmyadmin/phpmyadmin_pma_user_debnode.sql' => '/root/ha-inst-tmp/phpmyadmin_pma_user.sql',
                ),
                'posway' => 'after',
                'poskey' => null,
            ),

            'localhost:actionInit' => array(
                'type' => 'execute', 'value' => array(
                    //'systemctl disable mysql',
                    //'systemctl enable drbd.service',
                    //'#',
                    '# Now some information to output...',
                    'echo "TEST ACTION";',
                ),
                'posway' => 'after',
                'poskey' => null,
            ),

            'localhost:actionInitAfterNode2ActionInit' => array(
                'type' => 'execute', 'value' => array(
                    //'# checking df ....',
                    //'df -h',
                    //'# job done? Then:',
                    //'systemctl start ****.service',
                ),
                'posway' => 'after', 'poskey' => null,
            ),

            'localhost:actionInstallA' => array(
                'type' => 'execute', 'value' => array(
                    //'#',
                    //'# Now some information to output...',
                    //'#',
                    //'cmd',
                ),
                'posway' => 'after',
                'poskey' => null,
            ),
        ),
    ),
);
