<?php

namespace Gvozdb\Dumper\Logger;

use Monolog\Logger;

use Monolog\Formatter\LineFormatter;

use Monolog\Handler\GroupHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\TelegramBotHandler;
use Monolog\Handler\SwiftMailerHandler;

// use Swift_Mailer;

class Handler
{
    /**
     * @var array $config
     */
    protected $config = [];
    /**
     * @var \Monolog\Logger $logger
     */
    protected $logger;

    /**
     * @param array $config
     *
     * @throws \Exception
     */
    function __construct(array $config = [])
    {
        //
        $this->config = array_merge([
            'enabled' => false,
            'notify' => [],
        ], $config);
        if ($this->config['enabled'] !== true) {
            return;
        }

        //
        $handlers = [];
        foreach ($this->config['notify'] as $k => $v) {
            $v = array_merge([
                'level' => 'info',
                'format' => "[%datetime%] [%level_name%] > %message%\n",
                'dateFormat' => 'd.m.Y H:i:s',
            ], (@$v ?: []));
            $v['levelConstant'] = 'Monolog\Logger::' . strtoupper($v['level']);

            switch ($k) {
                case 'Console':
                    if (empty($v['path'])) {
                        break;
                    }
                    $handlers[$k] = new StreamHandler($v['path'], constant($v['levelConstant']));
                    break;

                case 'File':
                    if (empty($v['path'])) {
                        break;
                    }
                    $v['path'] = strftime($v['path']);
                    if (!preg_match('~^/~u', $v['path'])) {
                        $v['path'] = join('/', [
                            dirname(realpath($_SERVER['SCRIPT_FILENAME'])),
                            preg_replace('~^\./?~u', '', $v['path']),
                        ]);
                    }

                    $handlers[$k] = new StreamHandler($v['path'], constant($v['levelConstant']));
                    break;

                case 'Email':
                    if (empty($v['host'])) {
                        break;
                    }
                    $smtpTransporter = new \Swift_SmtpTransport($v['host'], $v['port'], $v['encryption']);
                    $smtpTransporter->setUsername($v['username']);
                    $smtpTransporter->setPassword($v['password']);
                    $swiftMailer = new \Swift_Mailer($smtpTransporter);
                    $swiftMessage = new \Swift_Message(strftime($v['subject']));
                    $swiftMessage->setFrom($v['from']);
                    $swiftMessage->setTo($v['to']);
                    $handlers[$k] = new BufferHandler(
                        new SwiftMailerHandler($swiftMailer, $swiftMessage, constant($v['levelConstant']))
                    );
                    break;

                case 'Telegram':
                    if (empty($v['token']) || empty($v['chat'])) {
                        break;
                    }
                    $handlers[$k] = new BufferHandler(new TelegramBotHandler(
                        $v['token'],
                        $v['chat'],
                        constant($v['levelConstant']),
                        true,
                        'HTML'
                    ), 0);
                    break;
            }

            if (!empty($handlers[$k])) {
                $handlers[$k]->setFormatter(new LineFormatter($v['format'], $v['dateFormat']));
            }
        }


        $this->logger = new Logger('Gvozdb\Dumper');
        $this->logger->pushHandler(new GroupHandler($handlers));
    }

    /**
     * @return void
     */
    public function bufferReset()
    {
        $handlers = $this->logger->getHandlers();
        foreach ($handlers as $handler) {
            $handler->reset();
        }
    }

    /**
     * @param string $action
     * @param string $message
     * @param array $context
     *
     * @return bool
     */
    public function log(string $action, string $message, array $context = [])
    {
        if (empty($this->logger)) {
            return false;
        }

        $message = trim(str_replace([
            '%title%',
        ], [
            @$this->config['title'] ?: '',
        ], $message));

        $this->logger->{$action}($message, $context);

        return true;
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return bool
     */
    public function debug($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return bool
     */
    public function info($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return bool
     */
    public function notice($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return bool
     */
    public function warning($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return bool
     */
    public function error($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return bool
     */
    public function critical($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return bool
     */
    public function alert($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return bool
     */
    public function emergency($message, array $context = [])
    {
        return $this->log(__FUNCTION__, $message, $context);
    }
}