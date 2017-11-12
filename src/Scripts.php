<?php

namespace Salaros\MrPress\Composer;

use Composer\Script\Event;
use Dotenv\Dotenv;
use qi\crontab\CrontabManager;
use Salaros\WordPress\SaltsGenerator;

class Scripts
{
    private static $vendorDir;
    private static $rootDir;
    private static $dotEnv;
    private static $wpCli;

    /**
     * Main method which initializes Scripts class instance
     * @param Event   $event  Script event object
     * @return void
     */
    public static function init(Event $event)
    {
        if (!empty(self::$vendorDir) && !empty(self::$rootDir) && !empty(self::$wpCli) && self::$dotEnv instanceof Dotenv) {
            return;
        }

        $composer = $event->getComposer();
        self::$vendorDir = $composer->getConfig()->get('vendor-dir');
        self::$rootDir = realpath(dirname(self::$vendorDir));

        require_once sprintf('%s/autoload.php', self::$vendorDir);

        self::$dotEnv = new Dotenv(self::$rootDir);
        self::$dotEnv->load();

        self::$wpCli = sprintf('%s/bin/wp', self::$vendorDir);
    }

    /**
     * Creating WordPress database via WP CLI by using configs loaded from .env file
     * @param Event   $event  Script event object
     * @return void
     */
    public static function createDatabase(Event $event)
    {
        self::init($event);

        self::$dotEnv->required(['DB_NAME', 'DB_USER', 'DB_PASSWORD'])->notEmpty();

        echo shell_exec(sprintf('%s --allow-root db drop --yes', self::$wpCli));
        echo shell_exec(sprintf('%s --allow-root db create', self::$wpCli));
    }

    /**
     * Install WordPress by creating tables on the DB via WP CLI
     * @param Event   $event  Script event object
     * @return void
     */
    public static function createTables(Event $event)
    {
        self::init($event);

        self::$dotEnv->required(['DB_NAME', 'DB_USER', 'DB_PASSWORD'])->notEmpty();

        $shellCmd = sprintf(
            '%s --allow-root core install 
                --url="%s" 
                --title="%s" 
                --admin_user="%s" 
                --admin_password="%s" 
                --admin_email="%s" 
                --skip-email',
            self::$wpCli,
            getenv('WP_HOME'),
            getenv('WP_TITLE'),
            getenv('WP_ADMIN'),
            getenv('WP_ADMIN_PASSWORD'),
            getenv('WP_ADMIN_EMAIL')
        );
        echo shell_exec($shellCmd);
    }


    /**
     * Activate all currently installed plugins
     * @param Event   $event  Script event object
     * @return void
     */
    public static function activatePlugins(Event $event)
    {
        self::init($event);

        self::$dotEnv->required(['DB_NAME', 'DB_USER', 'DB_PASSWORD'])->notEmpty();

        $pluginDir = sprintf('%s/wp-content/plugins', self::$rootDir);
        $pluginDirHandle = opendir($pluginDir);

        if (empty($pluginDirHandle)) {
            return; // TODO report: failed to open pugin directory
        }

        while (false !== ($entry = readdir($pluginDirHandle))) {
            $entryPath = sprintf('%s/%s', $pluginDir, $entry);
            if ($entry != '.' && $entry != '..' && is_dir($entryPath)) {
                $shellCmd = sprintf('%s --allow-root plugin activate %s', self::$wpCli, $entry);
                echo shell_exec($shellCmd);
            }
        }
    }

    /**
     * Create crontab entry for www-data user
     * @param Event   $event  Script event object
     * @return void
     */
    public static function createCronjob(Event $event)
    {
        self::init($event);

        if (1 !== intval(getenv('DISABLE_WP_CRON'))) {
            return;
        }

        $cronScript = sprintf('%s/public/wp-cron.php', self::$rootDir);

        $crontab = new CrontabManager();
        $crontab->user = 'www-data';

        $jobTicks = '*/2 * * * *';
        $jobCmd = sprintf('/usr/bin/php -f %s DOING_CRON=1 /dev/null 2>&1', $cronScript);
        $job_regex = str_replace('/', '\/', $jobCmd);
        if ($crontab->jobExists($job_regex)) {
            echo(sprintf("Not creating a cron job (%s). Because it already exists!\n", $jobCmd));
            return;
        }

        $job = $crontab->newJob();

        $job->on($jobTicks)->doJob($jobCmd);
        $crontab->add($job);

        $crontab->save(false);
    }

    public static function addSalts(Event $event)
    {
        self::init($event);


        $defaultSalts = [
            'AUTH_KEY',
            'SECURE_AUTH_KEY',
            'LOGGED_IN_KEY',
            'NONCE_KEY',
            'AUTH_SALT',
            'SECURE_AUTH_SALT',
            'LOGGED_IN_SALT',
            'NONCE_SALT',
            'WP_CACHE_KEY_SALT',
        ];

        $saltsAreOK = true;
        $envContents = file_get_contents(sprintf('%s/.env', self::$rootDir));
        foreach ($defaultSalts as $salt) {
            $saltsAreOK &= (false !== stripos($envContents, $salt));
            if (false === $saltsAreOK) {
                break;
            }
        }

        if ($saltsAreOK) {
            self::$dotEnv->required($defaultSalts)->notEmpty();
            printf(
                "Skipping WordPress salts generation, because .env already contains all the required variables\n"
            );
            return;
        }

        if (!SaltsGenerator::writeToFile( 'env', '.env', [ 'WP_CACHE_KEY_SALT' ] )) {
            printf("Failed to append WordPress salts to .env file!\n");
            return;
        }

        self::$dotEnv->overload();
        self::$dotEnv->required($defaultSalts)->notEmpty();

        printf("WordPress salts have been generated successfully!\n");
    }
}
