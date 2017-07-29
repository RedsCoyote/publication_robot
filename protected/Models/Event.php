<?php

namespace App\Models;

use App\Exceptions\BadDateTimeValueException;
use App\Exceptions\BadIntValueException;
use T4\Core\Std;

/**
 * Class Event
 * @package App\Models
 */
class Event extends Std
{
    public function change($data, $toSave)
    {
        $data['publicationStartDate'] = preg_replace('/[\.|\/]/', '-', $data['publicationStartDate']);
        if ($date = \DateTime::createFromFormat('d-m-y H:i', $data['publicationStartDate'] . ' ' . $data['publicationStartTime'], new \DateTimeZone('+0300'))) {
            $this->start_pub_date = $date->getTimestamp();
        } else {
            throw new BadDateTimeValueException('Bad date or time value in id = ' . $data['id']);
        }
        $streamId = $this->stream->id;
        $this->offsetUnset('stream');
        $this->stream = new \stdClass();
        $this->stream->id = $streamId;
        $this->offsetUnset('streams');
        $streams = [];
        foreach ($data['streams'] as $stream) {
            if ($stream === strval(intval($stream))) {
                $streamObj = new \stdClass();
                $streamObj->id = intval($stream);
                $streams[] = $streamObj;
            } else {
                throw new BadIntValueException('Bad integer value in id = ' . $data['id']);
            }
        }
        $this->streams = $streams;
        $changedLocations = [];
        foreach ($this->locations as $location) {
            $l = new \stdClass();
            $l->id = $location->id;
            $sessions = [];
            foreach ($location->sessions as $session) {
                $s = new \stdClass();
                $s->start_at = strtotime($session->start_at);
                $s->end_at = strtotime($session->end_at);
                $s->price = $session->price;
                $s->currency = $session->currency;
                $sessions[] = $s;
            }
            $l->sessions = $sessions;
            $changedLocations[] = $l;
        }
        $this->offsetUnset('locations');
        $this->locations = $changedLocations;
        $changedPictures = [];
        foreach ($this->pictures as $picture) {
            $p = new \stdClass();
            $p->id = $picture->id;
            $changedPictures[] = $p;
        }
        $this->offsetUnset('pictures');
        $this->pictures = $changedPictures;
        foreach (array_keys($this->getData()) as $key) {  // Удаляем поля, которые нам не нужны
            if (!in_array($key, $toSave)) {
                $this->offsetUnset($key);
            }
        }
    }
}

