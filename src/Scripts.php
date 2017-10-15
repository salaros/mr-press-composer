<?php

namespace Salaros\MrPress\Composer;

use Composer\Script\Event;
use Dotenv\Dotenv;
use qi\crontab\CrontabManager;

require_once sprintf('%s/autoload.php', self::$vendorDir);

class Scripts
{
    private static $vendorDir;
    private static $rootDir;
    private static $dotEnv;
    private static $wpCli;


    /**
     * Main method which initializes Scripts class instance
     *
     * @param Event   $event  Script event object
     * 
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

        self::$dotEnv = new Dotenv(self::$rootDir);
        self::$dotEnv->load();

        self::$wpCli = sprintf('%s/bin/wp', self::$vendorDir);
    }

    /**
     * Creating WordPress database via WP CLI by using configs loaded from .env file
     *
     * @param Event   $event  Script event object
     * 
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
     *
     * @param Event   $event  Script event object
     * 
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
     *
     * @param Event   $event  Script event object
     * 
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
     *
     * @param Event   $event  Script event object
     * 
     * @return void
     */ 
    public static function createCronjob(Event $event)
    {
        if (empty(getenv('DISABLE_WP_CRON'))) {
            return;
        }

        $cronScript = sprintf('%s/public/wp-cron.php', self::$rootDir);
        $crontab = new CrontabManager();
        $job = $crontab->newJob();
        $jobCmd = sprintf('/usr/bin/php %s /dev/null 2>&1', $cronScript);
        $job->on('*/2 * * * *')->doJob($jobCmd);
        $crontab->add($job);
        $crontab->save();
    }
}
