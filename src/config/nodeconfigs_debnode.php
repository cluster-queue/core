<?php // declare config

/**
 * Configs of debnode03 and debnode04 contain most infomations. debnode01 and debnode02
 * will be still worked out. maybe the order here is different.
 *
 * pre reserved action keys:
 *  - < nodekey >:defaultdeploy
 *  - < nodekey >:defaultarchive
 *  - < nodekey >:defaultactions
 *
 */

/*
testing

    192.168.26.210 debnode01            internal: 10.0.0.10
    192.168.26.211 debnode02            internal: 10.0.0.20
    192.168.26.212 debnode03            internal: 10.0.0.30
    192.168.26.213 debnode04            internal: 10.0.0.40
    192.168.26.214 spare                HA Web server   'debnodedb'
    192.168.26.215 spare                HA DB server    'debnodewww'
*/

return array(
    //
    // --- WEBSERVER HA PAIR
    //
    // www node 1 (pair of debnode01 & debnode02)
    //
    'debnode01' => array( // real machine hostname
/*        // future
//        'installconfig' => array(
//            // servertype = database | webserver
//            'servertype' => 'database',
//            // servertaget = live | internal;
//            //  live = only 'must have' packages
//            //  internal = packages + development extras/ tools
//            'servertaget' => 'internal',
//            # additional packages to install for that servers
//            'serverpacks' => 'heartbeat drbd-utils drbd-doc bwm-ng',
//        ),
*/

        // text replacements/ substitution in config files (templates) for
        // heartbeat, drbd, hosts config or other individuals you may want
        // node01 = debnode01
        // node02 = debnode02
        'replace' => array(
            //
            // replacements mostly for heartbeat and DRBD (www server group) configuration
            //
            'NODE01HOSTNAMEINT' => 'debnode01int', // internal hostname
            'NODE01HOSTNAMEPUB' => 'debnode01', // public hostname via dns
            'NODE02HOSTNAMEINT' => 'debnode02int', // internal hostname
            'NODE02HOSTNAMEPUB' => 'debnode02', // public hostname via dns

            // internal/public ip's to connect under the hood or from outside
            'NODE01IPADDRPUB' => '192.168.26.210', //eth1,
            'NODE01GWIPADDRPUB' => '192.168.26.1', // gateway IP eth1,

            'NODE01IPADDRINT' => '10.0.0.10', //eth0, debnode03
            'NODE01GWIPADDRINT' => '10.0.0.1', // gateway IP

            'NODE02IPADDRPUB' => '192.168.26.211', //eth1
            'NODE02GWIPADDRPUB' => '192.168.26.1', // gateway IP eth1,node2
            'NODE02IPADDRINT' => '10.0.0.20', //eth0,debnode04
            'NODE02GWIPADDRINT' => '10.0.0.1', // gateway IP node2

            'NODESHAREDHOSTNAME' => 'debnodewww', // for the dns to point to the shared ip
            'NODESHAREDIP' => '192.168.26.215', // HA IP for node01 or node02 for the activ one
            // for /etc/hosts, interfaces dns-search;
            // localhost.localdomain, 'domain.com' or NODECURRENTPUB
            'NODESDOMAINNAME' => 'debnode01',

            // shared secred for the nodes to comunicate to each other. for each node
            // group (eg. DB or www group for DRBD) the same.
            'NODESHAREDSECRED' => 'debnode-your-secure-password',

            'NODECURRENTINT' => 'debnode01int', // internal/ real hostname/ uname -n
            'NODECURRENTPUB' => 'debnode01', // key of this config! + hostname to be known in dns/ outside

            'NODE01SHAREDDISKDEVICE' => '/dev/sdb1', // 4GB 2nd disk
            'NODE02SHAREDDISKDEVICE' => '/dev/sdb1', // 4GB 2nd disk

            'NODEBCASTIFACENAME' => 'enp0s8', // iface for the HA IP
            'NODEETHIFACENAME01' => 'enp0s3', // node eth iface name eg eth0
            'NODEETHIFACENAME02' => 'enp0s8', // node eth iface name eg eth1

            // mail as smarthost otherwise create more variables to setup your MTA
            // update-exim4.conf den dc_smarthost='NODESMAILRELAYSMARTHOST'
            // postfix/main.cf: relayhost = 'NODESMAILRELAYSMARTHOST'
            'NODESMAILRELAYSMARTHOST' => '', // ip or hostname
            // mail domain name, localhost.localdomain
            // or NODECURRENTPUB, domain.com, /etc/mailname
            'NODESMAILDOMAINNAME' => 'debnode01',
            // eg: 'NODECURRENTPUB.NODESMAILDOMAINNAME'
            //     -> debnode03.domain.com
            'NODESMAILOTHEDOMAINNAME' => 'debnode01',
        ),

        // relevant files for the replacement for this node
        // starting in: './skel'
        'files' => array(
            '/generic/etc/drbd.d/r0.res' => '/etc/drbd.d/r0.res',
            '/generic/etc/ha.d/authkeys' => '/etc/ha.d/authkeys',
            '/generic/etc/ha.d/ha.cf' => '/etc/ha.d/ha.cf',
# @todo for nginx
            '/generic/etc/ha.d/haresources_httpd' => '/etc/ha.d/haresources',
            '/generic/etc/hostname' => '/etc/hostname',
            '/generic/etc/hosts_drbd' => '/etc/hosts',
            // debnode01: 1st node in drbd
            '/generic/etc/network/interfaces_nodeA' => '/etc/network/interfaces',
            # maybe needed 4 postfix or other debian mail related stuff
            '/generic/etc/mailname' => '/etc/mailname',
            // exim as smarthost
            '/generic/etc/exim4/update-exim4.conf.conf' => '/etc/exim4/update-exim4.conf.conf',
# @todo for nginx
            // nginx configs
            // no replacments in yet! php7.4-fpm enabled
            //'/generic/etc/nginx/sites-available/default' => '/etc/nginx/sites-available/default'
            //'/generic/etc/nginx/sites-available/poxysite' => '/etc/nginx/sites-available/proxysite',
        ),

        // Action configs/tokens to queue dependencies
        // Command types to execute: deploy, archive, execute
        // Note: software must be installed or an install task should be set before config
        // actions take affect.
        'actions' => array(
            //// job 1
            //array(
            //    jobKey: int | nodekey:customID => array(
            //        type: deploy|archive|execute,
            //        value: cmd|src=>target
            //        posway: before|after,
            //        poskey: nodekey:customID
            //        // default: 'posway' => 'after', 'poskey' => null,
            //    ),
            //)
            //...
            //),

            'debnode01:deploybuilds' => array(
                'type' => 'deploy', 'value' => array(
                    // flag to include the values from 'files' from above ('replace' key)
                    // only once per node config!
                    'files' => true,
                ),
                'posway' => 'after',
                'poskey' => null,
            ),

            'debnode01:deployfirst' => array(
                'type' => 'deploy', 'value' => array(
                    // some example
                    //'/generic/etc/nginx/sites-available/a' => '/etc/nginx/sites-available/a',

# @todo for phpmyadmin https://github.com/flobee/public/tree/master/etc/phpmyadmin/conf.d
# db node shared IP required!
#
                ),
                'posway' => 'after',
                'poskey' => null,
            ),

            'debnode01:actionInit' => array(
                'type' => 'execute', 'value' => array(
                    'systemctl disable nginx.service',
                    'systemctl stop nginx.service',

                    'systemctl stop drbd.service heartbeat.service',

                    'systemctl enable drbd.service',
                    'systemctl enable heartbeat.service',

                    'chmod 600 /etc/ha.d/authkeys',

                    '[ ! -d /var/www_bak ] && mv var/www var/www_bak',
                    '[ ! -d /var/www ] && mkdir /var/www',
                    'chown root:root /var/www',

                    // for the primary (node1)
                    'echo -e \'n\np\n1\n\n\nw\' | fdisk /dev/sdb', // create partition
                    'drbdadm create-md r0',
                    '#',
                    '# Check the docs before: Activation of DRBD (both nodes!):',
                    'systemctl restart drbd.service',
                    'drbdadm outdate r0',
                    'drbdadm -- --overwrite-data-of-peer primary all',
                    'drbdadm primary r0',
                    'mkfs.xfs /dev/drbd0',
                    '#',
                    '# Now at the second node until restart drbd and restart drbd',
                ),
                'posway' => 'after',
                'poskey' => null,
            ),

            'debnode01:actionInitAfterNode2ActionInit' => array(
                'type' => 'execute', 'value' => array(
                    '# checking sync of drbd devices:',
                    'df -h && cat /proc/drbd',

                    '# drbd sync done? Then:',
                    'systemctl start heartbeat.service',
                ),
                'posway' => 'after', 'poskey' => null,
            ),

            'debnode01:actionInstallA' => array(
                'type' => 'execute', 'value' => array(
                    '#',
                    '# reboot and test if its working before going on to configure server details',
                    '#',
                    '# for the activ and new WWW node:',
                    '# mounted?:',
                    '#   |- chown root:root /var/wwww',
                    '#   |- rsync -avP /var/www_bak/* /var/www',
# www stuff todo?
                ),
                'posway' => 'after',
                'poskey' => null,
            ),
        ),
    ),
//
//    //
//    // www node 2 (pair of debnode01 & debnode02)
//    //
//    'debnode02' => array( // real machine hostname
//        // text replacements/ substitution in skel/ config files for
//        // heartbeat, drbd, hosts config...
//        // node01 = debnode01
//        // node02 = debnode02
//        'replace' => array(
//            'NODECURRENTPUB' => 'debnode02',
//        ),
//
//        // relevant files for the replacement for this node
//        // starting in: './skel'
//        'files' => array(
//
//        ),
//
//        // Again: DANGER ZONE!
//        // commands to execute remote after deploy:
//        'actions' => array(
//
//        ),
//    ),

    //
    // --- DATABASE HA PAIR
    //
    // db node 1 (pair of debnode03 & debnode04)
    //
    'debnode03' => array( // real machine hostname
//        // future
//        'installconfig' => array(
//            // servertype = database | webserver
//            'servertype' => 'database',
//            // servertaget = live | internal;
//            //  live = only 'must have' packages
//            //  internal = packages + development extras/ tools
//            'servertaget' => 'internal',
//            # additional packages to install for that servers
//            'serverpacks' => 'heartbeat drbd-utils drbd-doc bwm-ng',
//        ),

        // text replacements/ substitution in config files (templates) for
        // heartbeat, drbd, hosts config or other individuals you may want
        // node01 = debnode03
        // node02 = debnode04
        'replace' => array(
            //
            // replacements mostly for heartbeat and DRBD configuration...
            //
            'NODE01HOSTNAMEINT' => 'debnode03int', // internal hostname
            'NODE01HOSTNAMEPUB' => 'debnode03', // public hostname via dns
            'NODE02HOSTNAMEINT' => 'debnode04int', // internal hostname
            'NODE02HOSTNAMEPUB' => 'debnode04', // public hostname via dns

            // internal/public ip's to connect under the hood or from outside
            'NODE01IPADDRPUB' => '192.168.26.212', //eth1,
            'NODE01GWIPADDRPUB' => '192.168.26.1', // gateway IP eth1,

            'NODE01IPADDRINT' => '10.0.0.30', //eth0, debnode03
            'NODE01GWIPADDRINT' => '10.0.0.1', // gateway IP

            'NODE02IPADDRPUB' => '192.168.26.213', //eth1
            'NODE02GWIPADDRPUB' => '192.168.26.1', // gateway IP eth1,node2
            'NODE02IPADDRINT' => '10.0.0.40', //eth0,debnode04
            'NODE02GWIPADDRINT' => '10.0.0.1', // gateway IP node2

            'NODESHAREDHOSTNAME' => 'debnodedb', // for the dns to point to the shared ip
            'NODESHAREDIP' => '192.168.26.214', // HA IP for node01 or node02 for the activ one
            // for /etc/hosts, interfaces dns-search;
            // localhost.localdomain, 'domain.com' or NODECURRENTPUB
            'NODESDOMAINNAME' => 'debnode03',

            // shared secred for the nodes to comunicate to each other. for each node
            // group (eg. DB or www group for DRBD) the same.
            'NODESHAREDSECRED' => 'debnode-your-secure-password',

            'NODECURRENTINT' => 'debnode03int', // internal/ real hostname/ uname -n
            'NODECURRENTPUB' => 'debnode03', // key of this config! + hostname to be known in dns/ outside

            'NODE01SHAREDDISKDEVICE' => '/dev/sdb1', // 4GB 2nd disk
            'NODE02SHAREDDISKDEVICE' => '/dev/sdb1', // 4GB 2nd disk

            'NODEBCASTIFACENAME' => 'enp0s8', // iface for the HA IP
            'NODEETHIFACENAME01' => 'enp0s3', // node eth iface name eg eth0
            'NODEETHIFACENAME02' => 'enp0s8', // node eth iface name eg eth1

            // mail as smarthost otherwise create more variables to setup your MTA
            // update-exim4.conf den dc_smarthost='NODESMAILRELAYSMARTHOST'
            // postfix/main.cf: relayhost = 'NODESMAILRELAYSMARTHOST'
            'NODESMAILRELAYSMARTHOST' => '', // ip or hostname
            // mail domain name, localhost.localdomain
            // or NODECURRENTPUB, domain.com, /etc/mailname
            'NODESMAILDOMAINNAME' => 'debnode03',
            // eg: 'NODECURRENTPUB.NODESMAILDOMAINNAME'
            //     -> debnode03.domain.com
            'NODESMAILOTHEDOMAINNAME' => 'debnode03',
        ),

        // relevant files for the replacement for this node
        // starting in: './skel'
        'files' => array(
            '/generic/etc/drbd.d/r0.res' => '/etc/drbd.d/r0.res',
            '/generic/etc/ha.d/authkeys' => '/etc/ha.d/authkeys',
            '/generic/etc/ha.d/ha.cf' => '/etc/ha.d/ha.cf',
            '/generic/etc/ha.d/haresources_mariadb' => '/etc/ha.d/haresources',
            '/generic/etc/hostname' => '/etc/hostname',
            '/generic/etc/hosts_drbd' => '/etc/hosts',
            // debnode03: 1st node in drbd
            '/generic/etc/network/interfaces_nodeA' => '/etc/network/interfaces',
            # maybe needed 4 postfix or other debian mail related stuff
            '/generic/etc/mailname' => '/etc/mailname',
            // exim as smarthost
            '/generic/etc/exim4/update-exim4.conf.conf' => '/etc/exim4/update-exim4.conf.conf',
        ),

        // Action configs/tokens to queue dependencies
        // Command types to execute: deploy, archive, execute
        // Note: software must be installed or an install task should be set before config
        // actions take affect.
        'actions' => array(
            //// job 1
            //array(
            //    jobKey: int | nodekey:customID => array(
            //        type: deploy|archive|execute,
            //        value: cmd|src=>target
            //        posway: before|after,
            //        poskey: nodekey:customID
            //        // default: 'posway' => 'after', 'poskey' => null,
            //    ),
            //)
            //...
            //),

            'debnode03:deploybuilds' => array(
                'type' => 'deploy', 'value' => array(
                    // flag to include the values from 'files' from above ('replace' key)
                    // only once per node config!
                    'files' => true,
                ),
                'posway' => 'after',
                'poskey' => null,
            ),

            'debnode03:deployfirst' => array(
                'type' => 'deploy', 'value' => array(
                    '/generic/etc/mysql/debian.cnf' => '/etc/mysql/debian.cnf', // must exists in skel/
                    '/generic/etc/mysql/mariadb.cnf' => '/etc/mysql/mariadb.cnf', // must exists in skel/
                    '/generic/etc/mysql/mariadb.conf.d/*' => '/etc/mysql/mariadb.conf.d/',
                    // For the active DB node (needs deploydefaults first!)
                    '/helper/phpmyadmin/phpmyadmin_create_tables.sql' => '/root/ha-inst-tmp/phpmyadmin_create_tables.sql',
                    '/helper/phpmyadmin/phpmyadmin_pma_user_debnode.sql' => '/root/ha-inst-tmp/phpmyadmin_pma_user.sql',
                ),
                'posway' => 'after',
                'poskey' => null,
            ),

            'debnode03:actionInit' => array(
                'type' => 'execute', 'value' => array(
                    'systemctl disable mysql',
                    'systemctl stop mariadb.service mysqld.service mysql.service',
                    'systemctl stop drbd.service heartbeat.service',

                    'systemctl enable drbd.service',
                    'systemctl enable heartbeat.service',

                    'chmod 600 /etc/ha.d/authkeys',

                    '[ ! -d /var/lib/mysql_bak ] && mv /var/lib/mysql /var/lib/mysql_bak',
                    '[ ! -d /var/lib/mysql ] && mkdir /var/lib/mysql',
                    'chown mysql:mysql /var/lib/mysql',

                    // for the primary (node1)
                    'echo -e \'n\np\n1\n\n\nw\' | fdisk /dev/sdb', // create partition
                    'drbdadm create-md r0',
                    '#',
                    '# Check the docs before: Activation of DRBD (both nodes!):',
                    'systemctl restart drbd.service',
                    'drbdadm outdate r0',
                    'drbdadm -- --overwrite-data-of-peer primary all',
                    'drbdadm primary r0',
                    'mkfs.xfs /dev/drbd0',
                    '#',
                    '# Now at the second node until restart drbd and restart drbd',
                ),
                'posway' => 'after',
                'poskey' => null,
            ),

            'debnode03:actionInitAfterNode2ActionInit' => array(
                'type' => 'execute', 'value' => array(
                    '# checking sync of drbd devices:',
                    'df -h && cat /proc/drbd',

                    '# drbd sync done? Then:',
                    'systemctl start heartbeat.service',
                ),
                'posway' => 'after', 'poskey' => null,
            ),

            'debnode03:actionInstallA' => array(
                'type' => 'execute', 'value' => array(
                    '#',
                    '# reboot and test if its working before going on to configure server details',
                    '#',
                    '# for the activ and new DB node:',
                    '# mounted?:',
                    '#   |- chown mysql:mysql /var/lib/mysql',
                    '#   |- rsync -avP /var/lib/mysql_bak/* /var/lib/mysql',
                    'mysql -u root < /root/ha-inst-tmp/phpmyadmin_create_tables.sql',
                    'mysql -u root < /root/ha-inst-tmp/phpmyadmin_pma_user.sql',
                ),
                'posway' => 'after',
                'poskey' => null,
            ),
        ),
    ),

    //
    // db node 2 (pair of debnode03 & debnode04)
    //
    'debnode04' => array( // real machine hostname
//        // future
//        'installconfig' => array(
//            // servertype = database | webserver
//            'servertype' => 'database',
//            // servertaget = live | internal;
//            //  live = only 'must have' packages
//            //  internal = packages + development extras/ tools
//            'servertaget' => 'internal',
//            # additional packages to install for that servers
//            'serverpacks' => 'heartbeat drbd-utils drbd-doc bwm-ng',
//        ),
//
        // text replacements/ substitution in config files (templates) for
        // heartbeat, drbd, hosts config or other individuals you may want
        // node01 = debnode03
        // node02 = debnode04
        'replace' => array(
            //
            // replacements mostly for heartbeat and DRBD configuration...
            //
            'NODE01HOSTNAMEINT' => 'debnode03int', // internal hostname
            'NODE01HOSTNAMEPUB' => 'debnode03', // public hostname via dns
            'NODE02HOSTNAMEINT' => 'debnode04int', // internal hostname
            'NODE02HOSTNAMEPUB' => 'debnode04', // public hostname via dns

            // internal/public ip's to connect under the hood or from outside
            'NODE01IPADDRPUB' => '192.168.26.212', //eth1,
            'NODE01GWIPADDRPUB' => '192.168.26.1', // gateway IP eth1,

            'NODE01IPADDRINT' => '10.0.0.30', //eth0, debnode03
            'NODE01GWIPADDRINT' => '10.0.0.1', // gateway IP

            'NODE02IPADDRPUB' => '192.168.26.213', //eth1
            'NODE02GWIPADDRPUB' => '192.168.26.1', // gateway IP eth1,node2
            'NODE02IPADDRINT' => '10.0.0.40', //eth0,debnode04
            'NODE02GWIPADDRINT' => '10.0.0.1', // gateway IP node2

            'NODESHAREDHOSTNAME' => 'debnodedb', // for the dns to point to the shared ip
            'NODESHAREDIP' => '192.168.26.214', // HA IP for node01 or node02 for the activ one
            // for /etc/hosts, interfaces dns-search;
            // localhost.localdomain, 'domain.com' or NODECURRENTPUB
            'NODESDOMAINNAME' => 'debnode03',

            // shared secred for the nodes to comunicate to each other. for each node
            // group (eg. DB or www group for DRBD) the same.
            'NODESHAREDSECRED' => 'debnode-your-secure-password',

            'NODECURRENTINT' => 'debnode04int', // internal/ real hostname/ uname -n
            'NODECURRENTPUB' => 'debnode04', // key of this config! + hostname to be known in dns/ outside

            'NODE01SHAREDDISKDEVICE' => '/dev/sdb1', // 4GB 2nd disk
            'NODE02SHAREDDISKDEVICE' => '/dev/sdb1', // 4GB 2nd disk

            'NODEBCASTIFACENAME' => 'enp0s8', // iface for the HA IP
            'NODEETHIFACENAME01' => 'enp0s3', // node eth iface name eg eth0
            'NODEETHIFACENAME02' => 'enp0s8', // node eth iface name eg eth1

            // mail as smarthost otherwise create more variables to setup your MTA
            // update-exim4.conf:dc_smarthost='NODESMAILRELAYSMARTHOST'
            // postfix/main.cf: relayhost = 'NODESMAILRELAYSMARTHOST'
            'NODESMAILRELAYSMARTHOST' => '', // ip or hostname
            // mail domain name, localhost.localdomain
            // or NODECURRENTPUB, domain.com, /etc/mailname
            'NODESMAILDOMAINNAME' => 'debnode03',
            // eg: 'NODECURRENTPUB.NODESMAILDOMAINNAME'
            //     -> debnode04.domain.com
            'NODESMAILOTHEDOMAINNAME' => 'debnode04',
        ),

        // relevant files for the replacement for this node
        // starting in: './skel'
        'files' => array(
            '/generic/etc/drbd.d/r0.res' => '/etc/drbd.d/r0.res',
            '/generic/etc/ha.d/authkeys' => '/etc/ha.d/authkeys',
            '/generic/etc/ha.d/ha.cf' => '/etc/ha.d/ha.cf',
            '/generic/etc/ha.d/haresources_mariadb' => '/etc/ha.d/haresources',
            '/generic/etc/hostname' => '/etc/hostname',
            '/generic/etc/hosts_drbd' => '/etc/hosts',
            // debnode04: 2nd node in drbd
            '/generic/etc/network/interfaces_nodeB' => '/etc/network/interfaces',
            # maybe needed 4 postfix or other debian mail related stuff
            '/generic/etc/mailname' => '/etc/mailname',
            // exim as smarthost
            '/generic/etc/exim4/update-exim4.conf.conf' => '/etc/exim4/update-exim4.conf.conf',
        ),

        // Action configs/tokens to queue dependencies
        // Command types to execute: deploy, archive, execute
        // Note: software must be installed or an install task should be set before config
        // actions take affect.
        'actions' => array(
            //// job 1
            //array(
            //    jobKey: int | nodekey:customID => array(
            //        type: deploy|archive|execute,
            //        value: cmd|src=>target
            //        posway: before|after,
            //        poskey: nodekey:customID
            //        // default: 'posway' => 'after', 'poskey' => null,
            //    ),
            //)
            //...
            //),

            'debnode04:deploybuilds' => array(
                'type' => 'deploy', 'value' => array(
                    // flag to include the values from 'files' from above ('replace' key)
                    // only once per node config!
                    'files' => true,
                ),
                'posway' => 'after',
                'poskey' => 'debnode03:deploybuilds',
            ),

            'debnode04:deployfirst' => array(
                'type' => 'deploy', 'value' => array(
                    '/generic/etc/mysql/debian.cnf' => '/etc/mysql/debian.cnf', // must exists in skel/
                    '/generic/etc/mysql/mariadb.cnf' => '/etc/mysql/mariadb.cnf', // must exists in skel/
                    '/generic/etc/mysql/mariadb.conf.d/*' => '/etc/mysql/mariadb.conf.d/',
                    // For the active DB node (needs deploydefaults first!)
                    '/helper/phpmyadmin/phpmyadmin_create_tables.sql' => '/root/ha-inst-tmp/phpmyadmin_create_tables.sql',
                    '/helper/phpmyadmin/phpmyadmin_pma_user_debnode.sql' => '/root/ha-inst-tmp/phpmyadmin_pma_user.sql',
                ),
                'posway' => 'after',
                'poskey' => 'debnode03:deployfirst', // queue in after debnode03:deployfirst
            ),

            'debnode04:actionInit' => array(
                'type' => 'execute', 'value' => array(
                    'systemctl disable mysql',
                    'systemctl stop mariadb.service mysqld.service mysql.service',
                    'systemctl stop drbd.service heartbeat.service',

                    'systemctl enable drbd.service',
                    'systemctl enable heartbeat.service',

                    'chmod 600 /etc/ha.d/authkeys',

                    '[ ! -d /var/lib/mysql_bak ] && mv /var/lib/mysql /var/lib/mysql_bak',
                    '[ ! -d /var/lib/mysql ] && mkdir /var/lib/mysql',
                    'chown mysql:mysql /var/lib/mysql',

                    // for the secondary (node2)
                    'echo -e \'n\np\n1\n\n\nw\' | fdisk /dev/sdb', // create partition
                    'drbdadm create-md r0',
                    '#',
                    '# Check the docs before: Activation of DRBD:',
                    'systemctl restart drbd.service',

                    '# checking sync of drbd devices:',
                    'df -h && cat /proc/drbd',
                ),
                'posway' => 'after',
                'poskey' => 'debnode03:actionInit', // queue in after debnode03:actionInit
            ),

            'debnode04:actionInitPostDeps' => array(
                'type' => 'execute', 'value' => array(
                    'systemctl start heartbeat.service',

                    '# reboot and test if drbd/heatbeat are working before',
                    '# going on to configure server details',
                 ),
                'posway' => 'after',
                'poskey' => 'debnode03:actionInitAfterNode2ActionInit', // queue in
            ),
        ),
    ),
);
