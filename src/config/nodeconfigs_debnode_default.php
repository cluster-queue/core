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
//        // start vm first if not activ
//        'NODECURRENTPUB:startvm' => array(
//            'type' => 'execute',
//            'value' => array(
//                // to have some scripts remotely available, create the path first
//                //'VBoxManage startvm \'NODECURRENTPUB\' --type headless && sleep 15' => array('type' => 'local'),
//                'VBoxManage startvm \'NODECURRENTPUB\' && sleep 10' => array('type' => 'local'),
//            ),
//            'posway' => 'after',
//            'poskey' => 'debnode03:startvm',
//        ),

        // before all other: create paths first, befor copy stuff to it
        'NODECURRENTPUB:defaultactionsCreateFirst' => array(
            'type' => 'execute',
            'value' => array(
                // to have some scripts remotely available, create the path first
                'mkdir -p /root/ha-inst-tmp' => array('type' => 'remote'),
            ),
            'posway' => 'after',
            'poskey' => null,
        ),

        // -------------------------------------------------
        // "deploy" list
        // Sources must be in 'skel/'
        'NODECURRENTPUB:defaultdeploy' => array(
            'type' => 'deploy',
            'value' => array(
                // from local 'skel/' => to remote
                '/testfile_NODECURRENTPUB.txt' => '/tmp/testfile.txt',
                '/testfile_NODECURRENTPUB.txt' => '/tmp/testfile_NODECURRENTPUB.txt',
            ),
            'posway' => 'after',
            'poskey' => null, // e.g: 'NODECURRENTPUB:defaultdeploy', or 'debnode01:defaultdeploy'
        ),

        // -------------------------------------------------
        // "archive" list
        // Archive important stuff we may need/want.
        // Sources must be in 'skel/'
        'NODECURRENTPUB:defaultarchive' => array(
            'type' => 'archive',
            'value' => array(
                // from remote => to local
                //'/tmp/testfile.txt' goes to 'archive/NODECURRENTPUB/testfile.txt',
                //'/tmp/*' => 'archive/NODECURRENTPUB/tmp/',
            ),
            'posway' => 'after',
            'poskey' => null,
        ),

        // -------------------------------------------------
        // "execute" list
        // With this you can also change the user to take action. "deploy" and "archive"
        // does it currently only with the root user. And sources must be in 'skel/'
        //
        // - array key is numeric: the value is the remote command
        // - array key is string: the key is the remote command, the value is an config
        //   list to set some possible properties to what to do:
        //      - 'type' => local|remote where to call ( local or remote )
        //                  Note: Commands may be local because they contain remote commands! ssh vs. scp
        //      - 'user'  optional to deside who should execute at the remote side (if type 'remote')
        'NODECURRENTPUB:defaultactions' => array(
            'type' => 'execute',
            'value' => array(
                'scp src/debNodesInstall.sh root@NODECURRENTPUB:/root/ha-inst-tmp' => array('type' => 'local'),
                'scp vendor/cluster-queue/core/src/confirmCommand.sh root@NODECURRENTPUB:/root/ha-inst-tmp' => array('type' => 'local'),
                'scp vendor/cluster-queue/core/src/shellFunctions.sh root@NODECURRENTPUB:/root/ha-inst-tmp' => array('type' => 'local'),

                'scp skel/generic/etc/apt/sources.list  root@NODECURRENTPUB:/etc/apt/sources.list' => array('type' => 'local'),
                'scp skel/generic/etc/apt/sources.list.d/* root@NODECURRENTPUB:/etc/apt/sources.list.d' => array('type' => 'local'),
                # .shell_aliases  .zsh_history  .zshrc  .zshrc.local  .zshrc.pre
                'scp skel/homes/.shell_aliases root@NODECURRENTPUB:/root/' => array('type' => 'local'),
                'scp skel/homes/.zshrc root@NODECURRENTPUB:/root/' => array('type' => 'local'),
                'scp skel/homes/.zshrc.local root@NODECURRENTPUB:/root/' => array('type' => 'local'),
                'scp skel/homes/.zshrc.pre root@NODECURRENTPUB:/root/' => array('type' => 'local'),
                'scp skel/homes/.gitconfig root@NODECURRENTPUB:/root/' => array('type' => 'local'),
                'scp skel/homes/.vimrc root@NODECURRENTPUB:/root/' => array('type' => 'local'),
                // to have some default commands
                'scp skel/homes/.mysql_history root@NODECURRENTPUB:/root/.mysql_history_init' => array('type' => 'local'),
                'if [ ! -f /root/.mysql_history ]; then cp /root/.mysql_history_init .mysql_history; fi' => array('type' => 'remote'),
                # keys to manage later on
                'scp skel/homes/.ssh/authorized_keys_root root@NODECURRENTPUB:/root/.ssh/authorized_keys' => array('type' => 'local'),
                'scp root@NODECURRENTPUB:/root/.ssh/id_rsa.pub ~/.ssh/id_rsa_NODECURRENTPUB.pub' => array('type' => 'local'),
                'scp root@NODECURRENTPUB:/root/.ssh/id_rsa ~/.ssh/id_rsa_NODECURRENTPUB' => array('type' => 'local'),
                // 4 linux user
                'scp skel/homes/.shell_aliases linux@NODECURRENTPUB:/home/linux/' => array('type' => 'local'),
                'scp skel/homes/.zshrc linux@NODECURRENTPUB:/home/linux/' => array('type' => 'local'),
                'scp skel/homes/.zshrc.local linux@NODECURRENTPUB:/home/linux/' => array('type' => 'local'),
                'scp skel/homes/.zshrc.pre linux@NODECURRENTPUB:/home/linux/' => array('type' => 'local'),
                'scp skel/homes/.gitconfig linux@NODECURRENTPUB:/home/linux/' => array('type' => 'local'),
                'scp skel/homes/.vimrc linux@NODECURRENTPUB:/home/linux/' => array('type' => 'local'),
            ),
            'posway' => 'after',
            'poskey' => null,
        ),

//        'NODECURRENTPUB:shutdown' => array(
//            'type' => 'execute',
//            'value' => array(
//                'sleep 1 && shutdown now',
//                //'sleep 10 && VBoxManage controlvm \'NODECURRENTPUB\' poweroff' => array('type' => 'local'),
//
//            ),
//            'posway' => 'after',
//            'poskey' => 'debnode04:defaultactions', // after the last job of the last node
//        ),

    ),
);
