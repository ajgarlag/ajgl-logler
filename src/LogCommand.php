<?php

/*
 * This file is part of the AJGL Logler utility
 *
 * Copyright (C) Antonio J. García Lagar <aj@garcialagar.es>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ajgl\Logler;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LogCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('log')
            ->setDescription('Enter a message into the system log.')
            ->addOption('id', 'i', InputOption::VALUE_NONE, 'Log the process ID of the logger process with each line.')
            ->addOption('priority', 'p', InputOption::VALUE_OPTIONAL, 'Enter the message into the log with the specified priority. The priority may be specified numerically, as a facility.level.  For example, -p local3.info logs the message as informational in the local3 facility. The default is user.notice.', 'user.notice')
            ->addOption('stderr', 's', InputOption::VALUE_NONE, 'Output the message to standard error as well as to the system log.')
            ->addOption('tag', 't', InputOption::VALUE_OPTIONAL, 'Mark every line to be logged with the specified tag.', '')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        list($facility, $severity) = $this->parsePriority($input->getOption('priority'));

        $syslogOptions = LOG_ODELAY;
        if ($input->getOption('id')) {
            $syslogOptions = $syslogOptions | LOG_PID;
        }

        if ($input->getOption('stderr')) {
            $syslogOptions = $syslogOptions | LOG_PERROR;
        }

        openlog($input->getOption('tag'), $syslogOptions, $facility);

        $stdin = fopen('php://stdin', 'r');
        //ob_implicit_flush (true); // Use unbuffered output
        while ($message = fgets($stdin)) {
            syslog($severity, $message);
        }

        closelog();
    }

    /**
     * Returns an array with two position:
     *   0 => PHP Facility constant
     *   1 => Severity level constant.
     *
     * @param string $priority
     */
    protected function parsePriority($priority)
    {
        if (is_numeric($priority)) {
            $facility = $priority / 8;
            $severity = $priority % 8;
        } elseif (preg_match('/^(auth(priv)?|cron|daemon|kern|lpr|mail|news|syslog|user|uucp|local[0-7])\.(emerg|alert|crit|err|warniing|notice|info|debug)$/', $priority)) {
            list($facility, $severity) = explode('.', $priority, 2);
        } else {
            throw new \InvalidArgumentException('Unexpected priority value');
        }

        return array(
            $this->parseFacility($facility),
            $this->parseSeverity($severity),
        );
    }

    /**
     * Return a PHP facility constant.
     *
     * @param mixed $facility
     *
     * @return int
     */
    private function parseFacility($facility)
    {
        switch ((string) $facility) {
            case '0':
            case 'kern':
                return LOG_KERN;
            default:
            case '1':
            case 'user':
                return LOG_USER;
            case '2':
            case 'mail':
                return LOG_MAIL;
            case '3':
            case 'daemon':
                return LOG_DAEMON;
            case '4':
            case 'auth':
                return LOG_AUTH;
            case '5':
            case 'syslog':
                return LOG_SYSLOG;
            case '6':
            case 'lpr':
                return LOG_LPR;
            case '7':
            case 'news':
                return LOG_NEWS;
            case '8':
            case 'uucp':
                return LOG_UUCP;
            case '9':
            case 'cron':
                return LOG_CRON;
            case '10':
            case 'authpriv':
                return LOG_AUTHPRIV;
            case '16':
            case 'local0':
                return LOG_LOCAL0;
            case '17':
            case 'local1':
                return LOG_LOCAL1;
            case '18':
            case 'local2':
                return LOG_LOCAL2;
            case '19':
            case 'local3':
                return LOG_LOCAL3;
            case '20':
            case 'local4':
                return LOG_LOCAL4;
            case '21':
            case 'local5':
                return LOG_LOCAL5;
            case '22':
            case 'local6':
                return LOG_LOCAL6;
            case '23':
            case 'local7':
                return LOG_LOCAL7;
        }
    }

    /**
     * Return a PHP severity constant
     *(emerg|alert|crit|err|warniing|notice|info|debug).
     *
     * @param mixed $severity
     *
     * @return int
     */
    private function parseSeverity($severity)
    {
        switch ((string) $severity) {
            case '0':
            case 'emerg':
                return LOG_EMERG;
            case '1':
            case 'alert':
                return LOG_ALERT;
            case '2':
            case 'crit':
                return LOG_CRIT;
            case '3':
            case 'err':
                return LOG_ERR;
            case '4':
            case 'warning':
                return LOG_WARNING;
            default:
            case '5':
            case 'notice':
                return LOG_NOTICE;
            case '6':
            case 'info':
                return LOG_INFO;
            case '7':
            case 'debug':
                return LOG_DEBUG;
        }
    }
}
