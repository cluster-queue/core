<?php declare(strict_types=1);

/* {{{ */
/**
 * QueueHelper for the 'cluster-queue' project
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
 * Queue deploy, archive and actions tasks and execute them.
 *
 * The first idea, this was made: We have some Servers: E.g. two DB and two WWW servers
 * in HA DRBD/ Heartbeat combination. They are raw installed with very minimum setup,
 * just with ssh to manage them remotely.
 *
 * Then Install required packages to each combination, deploy and configure them
 * automatically.
 *
 * The most needed task: copy configs and restart services. And also possible if you have
 * dependencies over the nodes. Where a service hangs because it is waiting for another
 * node. This tool was born to handle dependencies over the nodes.
 */
class QueueHelper
{
    /**
     * Version ID information.
     */
    const VERSION = '1.0.2';

    /**
     * Verbose/ debug mode
     * @var boolean
     */
    private $_debug = true;

    /**
     * Flag to enable real execution of commands.
     * Default OFF (false) for security reasons.
     * @var boolean
     */
    private $_execReal = false;

    /**
     * Flag to confirm each command before it will be executed.
     * @var bool
     */
    private $_execConfirm = false;

    /**
     * List of nodeName/list of configs @see nodeconfigs_*.php
     * @var array
     */
    private $_configs;

    /**
     * List of configs (deploy, archive, actions) belong to all nodes
     * see nodeconfigs_*_default.php
     * @var array
     */
    private $_configDefaults;

    /**
     * @var Mumsys_PriorityQueue_Simple
     */
    private $_queueManager;

    /**
     * Raw list of the queue per nodekey
     * @var array
     */
    private $_queueRaw = array();

    /**
     * Generated queue from $_queueRaw to perform actions or output
     * @var array
     */
    private $_queueList = array();

    /**
     * Optional logger to write/log massages
     * @var Toolbox_Logger_Interface
     */
    private $_logger = null;

    /**
     * Confirmation script location for actions
     * @var string
     */
    private $_confirmCommandLocation = 'src/confirmCommand.sh';


    /**
     * Init the object, verify and set configs to work with.
     *
     * @param array $input Incomming parameters
     * @param array $nodeConfigs Config for some concrete nodes
     * @param array $defaultConfigs Config for all nodes which are configured in $nodeConfigs
     * @param Mumsys_Logger_Interface $logger Logger interface
     *
     * @throws Exception On config errors or initialising dependencies
     */
    public function __construct( array $input, array $nodeConfigs,
        array $defaultConfigs = array(), Mumsys_Logger_Interface $logger = null )
    {
        if ( isset( $input['no-debug'] ) ) {
            $this->_debug = false;
        }

        if ( isset( $input['execute'] ) ) {
            $this->_execReal = true;
        }

        if ( isset( $input['confirm'] ) ) {
            $this->_execConfirm = true;
        }

        $this->_configs = $nodeConfigs;
        $this->_configDefaults = $defaultConfigs;

        if ( ($errs=$this->_verifyConfigsReplacementKeys())) {
            //print_r( array_values( $errs ) );
            $mesg = 'Error in replacement configs: ' . print_r( array_values( $errs ), true );
            throw new Exception( $mesg );
        }

        if ( $logger ) {
            $this->_logger = $logger;
            $logger->log('------------------------------------------------------------', 7);
            $logger->log('Process start: '. (string) date("Y-m-d H:i:s", time()), 7);
            $logger->log('Input params: ', 7);
            $logger->log( $input, 7);
        }

        $this->_queueManager = new Mumsys_PriorityQueue_Simple();

        if ( $this->_execConfirm ) {
            $this->_mesgStart( 'Confirmation of commands enabled.', 0, true );
            $this->_mesgEnd( '', true );
        }

        // misc checks
        if ( file_exists( $this->_confirmCommandLocation ) ) {
            // core project
        }
        else if ( file_exists( 'vendor/cluster-queue/core/src/confirmCommand.sh' ) ) {
            $this->_confirmCommandLocation = 'vendor/cluster-queue/core/src/confirmCommand.sh';
        }
        else {
            throw new Exception( 'ERR: Confirmation of commands - script not found!', 1 );
        }
    }


    /**
     * Creates/ builds config files for the target based on source template files in skel.
     *
     * - take configs,
     * - replace params and
     * - create a concrete config structure for a configured node
     *
     * @throws Exception On error with file handling
     */
    public function configsCreate(): void
    {
        $this->_mesgStart( '# Build/ create configs', 0, $this->_debug );
        $this->_mesgEnd('', $this->_debug);

        foreach ( $this->_configs as $nodekey => $nodeValues ) {

            // remove the nodes build dir first (if exists)
            $nodeDir = './build/' . $nodekey;
            if ( is_dir( $nodeDir ) && $this->_rmDir( $nodeDir ) === false ) {
                throw new Exception( 'Error clean up "build/' . $nodekey . '"' );
            }

            $replacementSearch = array_keys( $nodeValues['replace'] );
            $replacementReplace = array_values( $nodeValues['replace'] );

            foreach ( $nodeValues['files'] as $fileKey => $fileValue ) {
                if ( is_numeric( $fileKey ) ) {
                    $fileSrc = 'skel' . $fileValue;
                    $fileTarget = $fileValue;
                } else {
                    $fileSrc = 'skel' . $fileKey;
                    $fileTarget = $fileValue;
                }

                if ( ! file_exists( $fileSrc ) ) {
                    $mesg = sprintf( 'Src file not found ("%1$s"): "%2$s"', $nodekey, $fileSrc );
                    throw new Exception( $mesg );
                }

                $fileContent = file_get_contents( $fileSrc );
                $fileTarget = 'build/' . $nodekey . $fileTarget;

                $contentTarget = str_replace( $replacementSearch, $replacementReplace, $fileContent );

                // bring content to target
                $dirTarget = dirname( $fileTarget ) . '/';

                if ( !is_dir( $dirTarget ) ) {
                    $this->_mkDir( $dirTarget, 0755, true );
                }

                if ( file_put_contents( $fileTarget, $contentTarget ) === false ) {
                    throw new Exception( 'Error writing to file: "' . $fileTarget . '"' );
                }

                $this->_mesgStart( '# creates "'.$fileTarget.'"', 1, $this->_debug );
                $this->_mesgEnd('OK', $this->_debug);
            }
        }

    }


    /**
     * Creates/adds the defaults config (which are for ALL nodes) to the queue stack.
     *
     * @throws Exception
     */
    public function queueDefaults()
    {
        $this->_mesgStart( '# Queue defaults', 0, $this->_debug );
        $this->_mesgEnd('', $this->_debug);

        foreach ( $this->_configs as $nodekey => $nodeValues ) {

            foreach ( $this->_configDefaults['actions'] as $actionCodeTmp => $actionParts ) {

                // fill to real keys
                $actionCode = str_replace(
                    'NODECURRENTPUB',
                    $nodeValues['replace']['NODECURRENTPUB'],
                    $actionCodeTmp
                );

                // fill for real queue position code
                $actionParts['poskey'] = str_replace(
                    'NODECURRENTPUB',
                    $nodeValues['replace']['NODECURRENTPUB'],
                    $actionParts['poskey']
                );

                $this->_queueRaw[$nodekey][ $actionCode] = $actionParts;
            }
        }
    }


    /**
     * Adds the config actions queue list to the queue stack.
     */
    public function queueConfigs()
    {
        $this->_mesgStart( '# Queue configs', 0, $this->_debug );
        $this->_mesgEnd('', $this->_debug);

        foreach ($this->_configs as $nodekey => & $nodeParts) {
            foreach ($this->_configs[$nodekey]['actions'] as $actionCode => $actionParts) {
                $this->_queueRaw[$nodekey][ $actionCode ] = $actionParts;
            }
        }
    }


    /**
     * Build the final queue to perfom futher actions.
     */
    public function buildQueue()
    {
        $this->_mesgStart( '# Build queue/ job list', 0, $this->_debug );
        foreach ( $this->_queueRaw as $nodekey => $queueList ) {
            foreach ( $queueList as $code => $queuePart ) {
                $queuePart['value'] = $this->_substitude( $queuePart['value'], $this->_configs[$nodekey]['replace'] );
                $this->_queueManager->add( $code, $queuePart, $queuePart['posway'], $queuePart['poskey']);
            }
        }

        $this->_queueList = $this->_queueManager->getQueue();
        $this->_mesgEnd('OK', $this->_debug);

        if ( $this->_debug ) {
            $this->_mesgStart( '# Queue order (debug):', 1, $this->_debug );
            $this->_mesgEnd( '', $this->_debug );

            foreach ( $this->_queueList as $code => $tmp ) {
                $this->_mesgStart( '# ' . $code, 1, $this->_debug );
                $this->_mesgEnd( '', $this->_debug );
            }
        }
    }


    /**
     * Execute the parts of the queue stack.
     *
     * @param string $typeOnly deploy|archive|execute
     *
     * @throws Exception
     */
    public function run( string $typeOnly = null ): void
    {
        $indent = 0;
        $queueListResult = $this->_queueList;

        $typeOnlyText = '';
        if ($typeOnly) {
            $typeOnlyText = '"'. $typeOnly . '" ';
        }

        $this->_mesgStart( '# Run/ execute ' . $typeOnlyText . 'queue', $indent, $this->_debug );
        $this->_mesgEnd( '', $this->_debug );

        foreach ( $queueListResult as $code => $parts ) {
            // skip unwanted
            if ( $typeOnly && $typeOnly !== $parts['type'] ) {
                continue;
            }

            $this->_mesgStart( '# run ' . $typeOnlyText . 'queue "' . $code . '"', $indent + 1, $this->_debug );
            $this->_mesgEnd( '', $this->_debug );

            $codeParts = explode(':', $code);
            $hostname = $codeParts[0];

            switch ( $parts['type'] ) {
                case 'deploy':
                    $this->_runDeploy( $hostname, $parts['value']  );
                    break;

                case 'archive':
                    $this->_runArchive( $hostname, $parts['value'] );
                    break;

                case 'execute':
                    $this->_runActions( $hostname, $parts['value'] );
                    break;

                default:
                    $mesg = sprintf('Unknown type "%1$s"', $parts['type']);
                    throw new Exception( $mesg );
            }
        }
    }


    /**
     * Deploy list of src/target pairs to the remote host/ node.
     *
     * @param string $toHostName
     * @param array $list List of key/value pairs to deploy
     *
     * @throws Exception
     */
    private function _runDeploy( string $toHostName, array $list ): void
    {
        $pathPrefixSrc = 'skel';
        // eg scp skel/testfile root@node01:/tmp/testfile'
        $templateAction = 'scp %2$s root@%1$s:%3$s';

        // deploy build files?:
        if ( key( $list ) === 'files' && current( $list ) == true ) {
            $pathPrefixSrc = 'build';
            $list = array();

            if ( ! isset( $this->_configs[$toHostName] ) ) {
                throw new Exception( 'Config for "'. $toHostName .'" not found' );
            }

            foreach ( $this->_configs[$toHostName]['files'] as $file ) {
                if ( file_exists( $pathPrefixSrc . '/' . $toHostName . $file ) === false ) {
                    throw new Exception( 'Src file not found: "' . $file . '"' );
                }
                $list[ '/'.$toHostName . $file] = $file;
            }
        }

        $listResult = $this->_getFixedSourceTarget( $list, $pathPrefixSrc );

        foreach ( $listResult as $fileSrc => $fileTarget ) {
            $lastChar = substr( $fileSrc, -1 );
            switch ( $lastChar ) {
                case '*':
                    if ( ($tmp = glob( $fileSrc )) === false || empty( $tmp ) ) {
                        $mesg = sprintf(
                            'Sources not found for host "%1$s": "%2$s"', $toHostName, $fileSrc
                        );
                        throw new Exception( $mesg );
                    }
                    break;

                default:
                    if ( file_exists( $fileSrc ) === false ) {
                        $mesg = sprintf(
                            'Src file not found for host "%1$s": "%2$s"', $toHostName, $fileSrc
                        );
                        throw new Exception( $mesg );
                    }
                    break;
            }

            $cmd = sprintf( $templateAction, $toHostName, $fileSrc, $fileTarget );
            $this->execute( $cmd );
        }
    }


    /**
     * Archive list of src/target pairs from the remote host/ node to local.
     *
     * @param string $toHostName
     * @param array $list List of key/value pairs to archive
     *
     * @throws Exception
     */
    private function _runArchive( string $toHostName, array $list ): void
    {
        $pathPrefixSrc = '';
        // eg scp skel/testfile root@node01:/tmp/testfile'
        $templateAction = 'scp root@%1$s:%2$s %3$s';

        $listResult = $this->_getFixedSourceTarget( $list, $pathPrefixSrc );

        foreach ( $listResult as $fileSrc => $fileTarget ) {

            $lastChar = substr( $fileSrc, -1 );
            switch ( $lastChar ) {
                case '*':
                    // target dir creation
                    if ( !is_dir( $fileTarget ) ) {
                        $this->_mkDir($fileTarget, 0755, true);
                    }
                    break;

                default:
                    // tba
                    break;
            }

            $cmd = sprintf( $templateAction, $toHostName, $fileSrc, $fileTarget );
            $this->execute( $cmd );
        }
    }


    /**
     * Execute list of commands for the remote host/ node.
     *
     * @param string $toHostName
     * @param array $list  List of key/value pairs to execute

     * @throws Exception
     */
    private function _runActions( string $toHostName, array $list ): void
    {
        foreach ( $list as $key => $value ) {
            // key is numeric: the value is the remote command
            // key is string: the key is the remote command, the value is an array with
            //      'type'=>local|remote, optional 'user' to call remote
            if ( is_numeric( $key ) ) {
                $command = $value;
                $value = array('type' => 'remote');
            } else {
                $command = $key;
            }

            if ( !is_array( $value ) ) {
                throw new Exception( "Actions config invalid" );
            }

            if ( !isset( $value['type'] ) ) {
                throw new Exception( "Actions config type not set" );
            }

            $toUser = 'root';
            if ( isset( $value['user'] ) ) {
                $toUser = $value['user'];
            }

            switch ( $value['type'] ) {
                case 'local':
                    $call = $key;
                    break;

                case 'remote':
                    if ( $command[0] === '#' ) {
                        $call = $command;
                    } else {
                        $call = sprintf( 'ssh %1$s@%2$s \'%3$s\'', $toUser, $toHostName, $command );
                    }
                    break;
            }

            $this->execute( $call, $command );
        }
    }


    /**
     * Execute given command (wrapper).
     *
     * @param string $command Command to execute
     * @param string $realCommand Real command (if 'remote' call)
     */
    public function execute( string $command, string $realCommand = null ): void
    {
        $this->_mesgStart( $command, 2, true );
        // _mesgStatus closing below

        // skip comments for real excecution
        if ( empty( $realCommand ) || ($realCommand && $realCommand[0] !== '#') ) {
            $statusCode = $this->_executeShellCommand( $command );
        } else {
            $this->_mesgEnd( '#', true );
        }
    }


    /**
     * Execute the shell comand an check/ log returns.
     *
     * @param string $command
     */
    private function _executeShellCommand( string $command )
    {
        if ( $this->_execReal === false) {
            $this->_mesgEnd( 'nr', true ); // (n)ot (r)eal; (n)ot (r)equested

            return 0;
        }

        if ( $this->_execConfirm && $this->_executeShellCommandConfirm() === false ) {
            return 0;
        }

        $execMsgs = $execCode = null;
        $execLastLine = exec( $command, $execMsgs, $execCode );
        if ( $execCode != 0 ) {
            $this->_mesgEnd( 'ERR', true );

            $mesg = sprintf(
                'Command error: "%1$s"; Code: "%2$s" Output: %3$s %4$s',
                $command,
                $execCode,
                trim($execLastLine) . ';' . json_encode( $execMsgs ),
                PHP_EOL
            );

            $this->_mesgStart( $mesg, 3, true );
            $this->_mesgEnd( '', true );

            return $execCode;
        }
        else {
            $this->_mesgEnd( 'Success', true );
        }

        if ( $execMsgs && $this->_debug === true ) {
            $this->_mesgStart( 'Remote output found:', 3, true );
            $this->_mesgEnd( '', true );

            foreach ( $execMsgs as $mesg ) {
                $this->_mesgStart( $mesg, 4, true );
                $this->_mesgEnd( '', true );
            }
        }

        return 0;
    }


    /**
     * Requests local input to confirm to execute a command.
     *
     * @return boolean True to allow, false to not allow
     */
    private function _executeShellCommandConfirm()
    {
        $lastLine = exec( 'sh ' . $this->_confirmCommandLocation, $execMsgs, $execCode );
        if ( $execCode != 0 ) {
            $this->_mesgEnd( 'Skip', false );// just 4 the log

            return false;
        }

        return true;
    }


    /**
     * Fixes the list of src/target pairs with real source location by a prefix.
     *
     * @param array $list List of src/target pairs
     * @param string $pathPrefix Source prefix
     *
     * @return array Resulting array or empty if the list was empty
     */
    private function _getFixedSourceTarget( array $list, string $pathPrefix ): array
    {
        $result = array();
        foreach ( $list as $source => $target ) {
            if ( is_numeric( $source ) ) {
                $fileSrc = $pathPrefix . $target;
                $fileTarget = $target;
            } else {
                $fileSrc = $pathPrefix . $source;
                $fileTarget = $target;
            }

            $result[$fileSrc] = $fileTarget;
        }

        return $result;
    }


    /**
     * Replace key or value in the list of values.
     *
     * @param array $values List of key/value pairs to look for replacement values
     * @param array $replaces List of key/value pairs for the substitution where key is
     * the identifier and value the replacement
     *
     * @return array Resulting list of key/value pairs
     */
    private function _substitude( array $values, array $replaces ): array
    {
        $result = array();
        $replacementSearch = array_keys( $replaces );
        $replacementReplace = array_values( $replaces );
        foreach( $values as $key => $value) {
            if ($key === 'files' && $value === true) {
                $result[$key] = $value;
                continue;
            }

            $key = str_replace( $replacementSearch, $replacementReplace, $key );
            $value = str_replace( $replacementSearch, $replacementReplace, $value );
            $result[$key] = $value;
        }

        return $result;
    }


    /**
     * Checks replacement keys for problems with the name for duplicates or invalid names.
     *
     * Eg. a replacement will fail when having two similar keys: KEY=fail and KEYCHECK=me.
     * KEY will be replaced in KEYCHECK (failCHECK) and then KEYCHECK not exists anymore
     * and failCHECK wont be touched. This checks also in reverse.
     *
     * @return array List of error messages or empty for no error
     */
    private function _verifyConfigsReplacementKeys()
    {
        $errors = array();
        $errTempl = 'Key: "%1$s" matches key: "%2$s" in config: "%3$s"';

        foreach ( $this->_configs as $nodekey => $nodeValues ) {
            $keys = array_keys( $nodeValues['replace'] );
            $cnt = count( $keys );

            foreach ( $keys as $key ) {
                for ( $i = 0; $i < $cnt; $i++ ) {
                    if ( $key === $keys[$i] ) {
                        continue;
                    }

                    // ( a in b ) or ( b in a )
                    if ( preg_match( '/' . $keys[$i] . '/', $key ) ) {
                        $errCode = sprintf( $errTempl, $keys[$i], $key, $nodekey );
                        $errors[$errCode] = $errCode;
                    }

                    if ( preg_match( '/' . $key . '/', $keys[$i] ) ) {
                        $errCode = sprintf( $errTempl, $key, $keys[$i], $nodekey );
                        $errors[$errCode] = $errCode;
                    }
                }
            }
        }

        return $errors;
    }


    /**
     * Message handler pre.
     *
     * A line of a message starts and will be made to a fixed lenght with spaces.
     * After the fixed message lenght _mesgEnd() adds an small ending status text and end
     * the line with a linefeed (OS depending, eg: \n)
     * E.g: "some text to output..........................................OK\n"
     *
     * @param string $message Message/ command
     * @param int $level Indent level for output
     * @param boolean $show Show (true, default) or not false
     */
    public function _mesgStart( string $message, int $level = 0, $show = true )
    {
        $pre = '';
        for ( $i = 0; $i < 2 * $level; $i++ ) {
            $pre .= ' ';
        }

        $this->_logmesg = str_pad( $pre . $message, 120 );

        if ( $show ) {
            echo $this->_logmesg;
        }
    }


    /**
     * Message handler post.
     *
     * @see _mesgStart() for more information.
     *
     * @param string $status Current status
     * @param boolean $show  If true outputs the status
     */
    protected function _mesgEnd( $status, $show = true )
    {
        if ( $show ) {
            echo $status . PHP_EOL;
        }

        if ( $this->_logger ) {
            $this->_logger->log( $this->_logmesg . $status, 6 );
        }
    }


    /**
     * Removes a directory recusive.
     *
     * @param string $path Path to a Directory to delete everything below that
     */
    private function _rmDir( $path )
    {
        $files = glob( $path . '/*' );
        foreach ( $files as $file ) {
            is_dir( $file ) ? $this->_rmDir( $file ) : unlink( $file );
        }
        rmdir( $path );
    }


    /**
     * Creates a directory.
     *
     * @param string $path Path
     * @param octal $mode Mode in octal
     * @param boolean $recursiv Create recursiv in depth (true) or not (false, default)
     *
     * @return boolean Returns true on success
     * @throws Exception If creation of the $path fails
     */
    private function _mkDir( string $path, $mode = 0755, $recursiv = false ): bool
    {
        if ( mkdir( $path . '/', $mode, $recursiv ) === false ) {
            $mesg = sprintf( 'mkdir() failed for: "%1$s"', $path );
            throw new Exception( $mesg );
        }

        return true;
    }

}
