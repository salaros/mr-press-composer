<?php

namespace Salaros\Wordpress\MrPress\Composer;

use Composer\Script\Event;
use Dotenv\Dotenv;

class Scripts
{
    private static $vendorDir;
    private static $rootDir;
    private static $dotEnv;
    private static $wpCli;

    public static function init(Event $event)
    {
        if (!empty(self::$vendorDir) && !empty(self::$rootDir) && !empty(self::$wpCli) && self::$dotEnv instanceof Dotenv) {
            return;
        }

        $composer = $event->getComposer();
        self::$vendorDir = $composer->getConfig()->get('vendor-dir');
        self::$rootDir = preg_replace('/\/vendor/', '', self::$vendorDir);

        require_once sprintf('%s/autoload.php', self::$vendorDir);

        self::$dotEnv = new Dotenv(self::$rootDir);
        self::$dotenv->load();

        self::$wpCli = sprintf('%s/bin/wp', self::$vendorDir);
    }

    public static function createDatabase(Event $event)
    {
        self::init($event);

        self::$dotenv->required(['DB_NAME', 'DB_USER', 'DB_PASSWORD'])->notEmpty();

        exec(sprintf('%s --allow-root db drop --yes', self::$wpCli));
        exec(sprintf('%s --allow-root db create', self::$wpCli));
    }

    public static function createTables(Event $event)
    {
        self::init($event);

        self::$dotenv->required(['DB_NAME', 'DB_USER', 'DB_PASSWORD'])->notEmpty();

        exec(sprintf('%s --allow-root core install --url=%s --title=%s --admin_user=%s --admin_password=%s --admin_email=%s --skip-email',
                      self::$wpCli,
                      getenv('WP_HOME'),
                      getenv('WP_TITLE'),
                      getenv('WP_ADMIN'),
                      getenv('WP_ADMIN_PASSWORD'),
                      getenv('WP_ADMIN_EMAIL')));
    }

    public static function activatePlugins(Event $event)
    {
        self::init($event);

        self::$dotenv->required(['DB_NAME', 'DB_USER', 'DB_PASSWORD'])->notEmpty();

        $pluginDir = sprintf('%s/wp-content/plugins', self::$rootDir);
        while (false !== ($entry = $pluginDir->read())) {
            $entryPath = sprintf('%s/%s', $pluginDir, $entry);
            if ($entry != '.' && $entry != '..' && is_dir($entryPath)) {
                exec(sprintf('%s vendor/bin/wp --allow-root plugin activate %s', self::$wpCli));
            }
        }
    }
}