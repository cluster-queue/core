<?php // declare config

/**
 * Default configs for all nodes.
 *
 * List of 'action' configs to perform in this order (if not changing posway/poskey)
 * on ALL nodes in your nodes config!
 *
 * If you have pre/post dependencies in your nodes config then parts of this config
 * may be handled at a later time you may expect but it would be the logical order.
 *
 * You may change order dependencies. E.g. set an empty root element an bind them in the
 * way you may need them here or in your nodes config.
 *
 * The identifier for the default config is: 'NODECURRENTPUB:your-code' where
 * NODECURRENTPUB will be replaced with the current value of the nodes config when parsing
 * the liste. So you could use the keys in your nodes config to set dependencies.
 *
 * Deploy may fail because a path at the target doesnt exists yet.
 * So some tasks must be handled as action/execution first, then eg. deploy someting.
 * See below.
 */
return array(

    'actions' => array(

//        'NODECURRENTPUB:defaultactionsCreateFirst' => array(
//            'type' => 'execute | deploy | archive',
//            'value' => array(
//                // 'vmstart NODECURRENTPUB'
//                // 'sleep 20'
//
//                // before all other: create a path first, befor copy stuff to it
//                // 'mkdir -p /root/install-tmp' => array('type'=>'remote'),
//            ),
//            'posway' => 'after',
//            'poskey' => null,
//        ),

        // "execute" list
        // - array key is numeric: the value is the remote command
        // - array key is string: the key is the remote command, the value is an config
        //   list to set some possible properties to what to do:
        //      - 'type' => local|remote where to call ( local or remote )
        //                  Note: Commands may be local (scp) because they contain remote
        //                  commands! ssh vs. scp
        //      - 'user'  optional to deside who should execute at the remote side (if type 'remote')
        'NODECURRENTPUB:defaultactions' => array(
            'type' => 'execute',
            'value' => array(
                // demo
                //'touch /home/linux/test-deploy-with-user' => array('type'=>'remote', 'user'=> 'linux'),
                //'rm /home/linux/test-deploy-with-user' => array('type'=>'remote', 'user'=> 'linux'),

                // see above '/root/install-tmp' to have some scripts remotely available
                //'cp somefile /root/install-tmp' => array('type'=>'remote'),

                // other actions to execute here in this job group
            ),
            'posway' => 'after',
            'poskey' => null,
        ),

        // further execute | deploy | archive jobs

    ),
);
