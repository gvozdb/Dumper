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

class YandexDisk extends AbstractStorage
{
    /**
     * @var array $config
     */
    protected $config = [];
    /**
     * @var \Arhitector\Yandex\Disk $client
     */
    protected $client;

    /**
     * @param array $config
     *
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        //
        if (empty($this->config['token'])) {
            throw new \Exception('Token is empty.');
        }
        $this->client = new \Arhitector\Yandex\Disk($this->config['token']);
    }

    /**
     * @param string $filepath
     *
     * @return bool
     * @throws \Exception
     */
    public function upload($filepath)
    {
        if (empty($filepath)) {
            return false;
        }

        try {
            if (!$path = $this->getPath()) {
                return false;
            }

            $filename = pathinfo($filepath, PATHINFO_BASENAME);
            $resource = $this->client->getResource($path . $filename);
            if ($resource->has()) {
                $resource->delete(true);
            }
            $resource->upload($filepath);
            unset($resource);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return true;
    }

    /**
     *
     */
    public function clean()
    {
        // Удаляем устаревшие бекапы
        if ($expires_time = (86400 * $this->config['expires'])) {
            $expires_time = time() - $expires_time;
            $parent = $this->client->getResource($this->getParentPath());
            $childs = $parent->getIterator();

            /** @var \Arhitector\Yandex\Disk\Resource\Closed $child */
            foreach ($childs['items'] as $child) {
                $created_time = strtotime(date('Y-m-d', strtotime($child->get('created'))));
                if ($expires_time > $created_time) {
                    $child->delete(true);
                }
            }
        }
    }

    /**
     * @return string
     */
    protected function getPath()
    {
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

        return $this->config['path'];
    }

    /**
     * @return string
     */
    protected function getParentPath()
    {
        return dirname($this->getPath()) . '/';
    }
}