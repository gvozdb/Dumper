<?php

/**
 * Как получить токен:
 * 1) Регистрируем приложение
 * 1.1) Заходим https://oauth.yandex.ru из под юзера, который будет владеть приложением
 * 1.2) Зарегистрировать новое приложение
 * 1.3) Платформы: Веб-сервисы -> Подставить URL для разработки
 * 1.4) Доступы: Яндекс.Диск REST API -> Отметить всё
 * 1.5) Создать приложение
 * 2) Даём доступ к своему Яндекс.Диску новому приложению
 * 2.1) Вставляем ID приложения в УРЛ https://oauth.yandex.ru/authorize?response_type=token&client_id={ID}
 * 2.2) Переходим по УРЛ из под юзера, которому будем заливать бекапы
 * 2.3) Разрешить
 * 2.4) Копируем токен
 */

namespace Gvozdb\Dumper\Storage;

use Gvozdb\Dumper\Backup;

class YandexDisk extends AbstractStorage
{
    /**
     * @var \Arhitector\Yandex\Disk $client
     */
    protected $client;


    /**
     * @param Backup $dumper
     * @param array $config
     *
     * @throws \Exception
     */
    public function __construct(Backup $dumper, array $config = [])
    {
        parent::__construct($dumper, $config);

        try {
            $this->client = new \Arhitector\Yandex\Disk($this->config['token']);

            // Check authorization status
            $this->getPath();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }


    /**
     * @return bool
     */
    public function enabled()
    {
        if (($flag = parent::enabled()) === true) {
            foreach (['token'] as $k) {
                if (empty($this->config[$k])) {
                    $flag = false;
                }
            }
        }

        return $flag;
    }


    /**
     * @param string $filepath
     *
     * @return void|bool
     *
     * @throws \Exception
     */
    public function upload($filepath)
    {
        if ($this->enabled() === false) {
            return;
        }
        if (empty($filepath)) {
            return false;
        }

        do {
            $try = isset($try) ? ++$try : 0;
            try {
                if (!$path = $this->getPath()) {
                    return false;
                }

                $filename = pathinfo($filepath, PATHINFO_BASENAME);
                $resource = $this->client->getResource($path . $filename);
                if ($resource->has()) {
                    $resource->delete(true);
                }

                $resource->addListener(
                    'progress',
                    function (\League\Event\Event $event, $percent) use ($filename) {
                        $this->dumper->progressBar($percent, 100, $filename);
                    }
                );

                $resource->upload($filepath);

                break;
            } catch (\Exception $e) {
                if ($try >= 4) { // 4 tries
                    throw new \Exception($e->getMessage());
                }
                sleep(5);
            }
        } while (true);

        return true;
    }


    /**
     * @return void|bool
     *
     * @throws \Exception
     */
    public function clean()
    {
        if ($this->enabled() === false) {
            return;
        }
        $expires = $this->config['expires'];

        try {
            // Get a list of backup folders in the cloud
            $childs = [];
            /** @var \Arhitector\Yandex\Disk\Resource\Closed $resource */
            $resources = $this->client->getResource($this->getParentPath())
                ->setSort('created', true)
                ->getIterator();
            foreach ($resources['items'] as $resource) {
                $childs[] = [
                    'id' => $resource->get('resource_id'),
                    'created' => $resource->get('created'),
                ];
            }
            if (count($childs) > 1) {
                // Collect a list of backup folder id's to keep
                $ids = ['short' => [], 'long' => []];
                $today_time = $this->getStartDayUtc(time()); // $childs[0]['created']
                $last_time = $this->getStartDayUtc($childs[count($childs) - 1]['created']);
                foreach (array_keys($ids) as $k) {
                    $expires_time = $today_time - (86400 * $expires[$k . '_max_days']);
                    $expires_max_items = ceil($expires[$k . '_max_days'] / $expires[$k . '_step']);
                    foreach ($childs as $child) {
                        if (in_array($child['id'], $ids['short'], true)) {
                            continue;
                        }
                        $child_time = $this->getStartDayUtc($child['created']);
                        $day_num = ($child_time - $last_time) / 86400;

                        if ($today_time === $child_time || ($expires_time <= $child_time && ($day_num % $expires[$k . '_step']) === 0)) {
                            $ids[$k][] = $child['id'];
                        }
                    }
                    $ids[$k] = array_slice($ids[$k], 0, $expires_max_items);
                }
                $ids = array_unique(array_merge($ids['short'], $ids['long']));
                $childs = array_values(
                    array_map(
                        function ($child) {
                            return $child['id'];
                        },
                        array_filter($childs, function ($child) use ($ids) {
                            return in_array($child['id'], $ids, true);
                        })
                    )
                );

                // Remove backup folders from the cloud that don't suit us
                foreach ($resources['items'] as $resource) {
                    if (!in_array($resource->get('resource_id'), $childs, true)) {
                        $resource->delete(true);
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return true;
    }


    /**
     * @return string
     *
     * @throws \Exception
     */
    protected function getPath()
    {
        try {
            $path = 'disk:/';
            $folders = explode('/', $this->config['path']);
            foreach ($folders as $v) {
                if (empty($v) || $v == 'disk:') {
                    continue;
                }
                $path .= $v . '/';
                $resource = $this->client->getResource($path);
                if (!$resource->has()) {
                    $resource->create();
                }
                unset($resource);
            }
            unset($folders);
            $this->config['path'] = $path;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $this->config['path'];
    }


    /**
     * @return string
     *
     * @throws \Exception
     */
    protected function getParentPath()
    {
        $path = $this->getPath();
        if (is_string($path)) {
            $path = dirname($path) . '/';
        }

        return $path;
    }


    /**
     * @param string|int $date
     *
     * @return int
     */
    protected function getStartDayUtc($date)
    {
        $timestamp = is_numeric($date) ? $date : strtotime($date);

        return strtotime(date('Y-m-d', $timestamp) . 'T00:00:00 UTC');
    }
}