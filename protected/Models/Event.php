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
        if ($this->start_pub_date = strtotime($data['publicationStartDate'] . ' ' . $data['publicationStartTime'])) {
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
        foreach (array_keys($this->getData()) as $key) {  // Удаляем поля, которые нам не нужны
            if (!in_array($key, $toSave)) {
                $this->offsetUnset($key);
            }
        }
    }
}

