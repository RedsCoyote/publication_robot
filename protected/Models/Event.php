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
    public function __construct($data = null)
    {
        $data = $this->validateAndSanitize($data);
        $stream = $data['stream'];
        $streams = $data['streams'];
        unset($data['streams']);
        unset($data['stream']);
        parent::__construct($data);
        $this->stream = $stream;
        $this->streams = $streams;
    }

    private function validateAndSanitize($data)
    {
        if ($data['id'] === strval(intval($data['id']))) {
            $data['id'] = intval($data['id']);
            if ($data['stream'] === strval(intval($data['stream']))) {
                $data['stream'] = ['id' => intval($data['stream'])];
                foreach ($data['streams'] as $key => $stream) {
                    if ($stream === strval(intval($stream))) {
                        $data['streams'][$key] = ['id' => intval($stream)];
                    } else {
                        throw new BadIntValueException('Bad integer value in id = ' . $data['id']);
                    }
                }
                $data['publicationStartDate'] = preg_replace('/[\.|\/]/', '-', $data['publicationStartDate']);
                if ($data['start_pub_date'] = strtotime($data['publicationStartDate'] . ' ' . $data['publicationStartTime'])) {
                    unset($data['publicationStartDate']);
                    unset($data['publicationStartTime']);
                    $data['publicationEndDate'] = preg_replace('/[\.|\/]/', '-', $data['publicationEndDate']);
                    if ($data['end_pub_date'] = strtotime($data['publicationEndDate'] . ' ' . $data['publicationEndTime'])) {
                        unset($data['publicationEndDate']);
                        unset($data['publicationEndTime']);
                        return $data;
                    }
                }
                throw new BadDateTimeValueException('Bad date or time value in id = ' . $data['id']);
            }
        }
        throw new BadIntValueException('Bad integer value in id = ' . $data['id']);
    }
}

