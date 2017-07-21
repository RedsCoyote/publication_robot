<?php

namespace App\Commands;

use App\Core\Logger;
use App\Exceptions\InitAPIException;
use App\Google\Api;
use App\Junglefox\JungleFoxAPI;
use App\Models\Event;
use Google_Service_Sheets;
use T4\Console\Command;

class Robot extends Command
{
    protected $logger = null;
    /**
     * @var JungleFoxAPI $jfApi
     */
    protected $jfApi = null;

    /**
     * Действие по-умолчанию
     */
    public function actionDefault()
    {
        $this->actionRun();
    }

    /**
     * Запуск публикации
     */
    public function actionRun()
    {
        $this->updateEvents(
            $this->loadPublicationEvents(
                (new Api())->getService()
            )
        );
    }

    private function updateEvents($events = null)
    {
        if ($events) {
            foreach ($events as $event) {
                //
            }
        }
    }

    private function loadPublicationEvents(Google_Service_Sheets $service)
    {
        $values = $service->spreadsheets_values
            ->get($this->app->config->spreadsheets->id, $this->app->config->spreadsheets->range)
            ->getValues();
        if (0 != count($values)) {  // Есть, что публиковать
            $keys = $this->app->config->spreadsheets->columns->getData();
            $keyLength = count($keys);
            $events = [];
            foreach ($values as $row) {
                $eventData = array_merge(
                    array_combine($keys, array_slice($row, 0, $keyLength)),
                    ['streams' => array_slice($row, $keyLength)]
                );
                try {
                    $events[] = new Event($eventData);
                } catch (\Exception $e) {
                    echo $e->getMessage();
                }
            }
            return $events;
        }
        return null;
    }

    /**
     * Действия, выполняемые до любой команды
     * @return bool Если возвращается false, то дальнейшая команда игнорируется
     */
    protected function beforeAction()
    {
        $config = $this->app->config;
        $this->logger = new Logger($config);
        try {
            $this->jfApi = new JungleFoxAPI($config, $this->logger);
        } catch (InitAPIException $e) {
            $this->logger->error('Can\'t init API. Application terminated.');
            return false;
        }
        return parent::beforeAction();
    }

}
