<?php

namespace App\Commands;

use App\Core\Logger;
use App\Exceptions\BadDateTimeValueException;
use App\Exceptions\EventException;
use App\Exceptions\InitAPIException;
use App\Exceptions\StreamsException;
use App\Google\Api;
use App\Junglefox\JungleFoxAPI;
use Google_Service_Sheets;
use T4\Console\Command;

class Robot extends Command
{
    protected $logger;
    /**
     * @var JungleFoxAPI $jfApi
     */
    protected $jfApi;

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
        if ($eventsData = $this->loadPublicationEventsData((new Api())->getService())) {
            foreach ($eventsData as $values) {
                if ($values['id'] === (string)((int)$values['id'])) {
                    try {
                        if (!$this->isOldPublicationDate($values)) {
                            $event = $this->jfApi->getEvent($values['id']);
                            if ($event) {
                                try {
//                                $this->jfApi->delStreams($event);
                                    $event->change($values, $this->app->config->spreadsheets->fields->getData());
                                    $this->jfApi->addStreams($event);
                                    $this->jfApi->updateEvent($event);
                                } catch (EventException $e) {
                                    continue;
                                } catch (StreamsException $e) {
                                    continue;
                                }
                            }
                        }
                    } catch (BadDateTimeValueException $e) {
                        continue;
                    }
                }
            }
        }
    }

    private function loadPublicationEventsData(Google_Service_Sheets $service)
    {
        $values = $service->spreadsheets_values
            ->get($this->app->config->spreadsheets->id, $this->app->config->spreadsheets->range)
            ->getValues();
        if (0 !== count($values)) {  // Есть, что публиковать
            $keys = $this->app->config->spreadsheets->columns->getData();
            $keyLength = count($keys);
            $eventsData = [];
            foreach ($values as $row) {
                $eventsData[] = array_merge(
                    array_combine($keys, array_slice($row, 0, $keyLength)),
                    ['streams' => array_slice($row, $keyLength)]
                );
            }
            return $eventsData;
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

    /**
     * Проверяет актуальность текущей строки файла с заданиями
     * Если текущее время больше времени публикации, то публиковать уже не надо
     * @param $data
     * @return bool
     * @throws BadDateTimeValueException
     */
    protected function isOldPublicationDate($data)
    {
        $data['publicationStartDate'] = preg_replace('/[\.|\/]/', '-', $data['publicationStartDate']);

        if ($date = \DateTime::createFromFormat('d-m-y H:i', $data['publicationStartDate'] . ' ' . $data['publicationStartTime'], new \DateTimeZone('+0300'))) {
            return $date->getTimestamp() < (new \DateTime('now', new \DateTimeZone('+0300')))->getTimestamp();
        }

        throw new BadDateTimeValueException('Bad date or time value in id = ' . $data['id']);
    }
}
