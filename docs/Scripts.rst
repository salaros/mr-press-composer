-----------------------------------
Salaros\\MrPress\\Composer\\Scripts
-----------------------------------

.. php:namespace: Salaros\\MrPress\\Composer

.. php:class:: Scripts

    Composer scripts for Mr.Press installations

    .. php:method:: init(Event $event)

        Main method which initializes Scripts class instance

        :type $event: Event
        :param $event:
        :returns: void

    .. php:method:: createDatabase(Event $event)

        Creating WordPress database via WP CLI by using configs loaded from .env
        file

        :type $event: Event
        :param $event:
        :returns: void

    .. php:method:: createTables(Event $event)

        Install WordPress by creating tables on the DB via WP CLI

        :type $event: Event
        :param $event:
        :returns: void

    .. php:method:: activatePlugins(Event $event)

        Activate all currently installed plugins

        :type $event: Event
        :param $event:
        :returns: void

    .. php:method:: createCronjob(Event $event)

        Create crontab entry for www-data user

        :type $event: Event
        :param $event:
        :returns: void

    .. php:method:: addSalts(Event $event)

        Appends WordPress salts to .env file if needed

        :type $event: Event
        :param $event:
        :returns: void
