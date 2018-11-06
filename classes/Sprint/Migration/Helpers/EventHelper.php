<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class EventHelper extends Helper
{

    public function getEventTypeList($eventName) {
        $filter = is_array($eventName) ? $eventName : array(
            'TYPE_ID' => $eventName,
        );

        $dbres = \CEventType::GetList($filter);
        return $this->fetchAll($dbres);
    }

    public function getEventMessage($eventName) {
        $filter = is_array($eventName) ? $eventName : array(
            'TYPE_ID' => $eventName,
        );

        $by = 'id';
        $order = 'asc';

        $dbres = \CEventMessage::GetList($by, $order, $filter);
        return $dbres->Fetch();
    }

    public function getEventMessages($eventName) {
        $filter = is_array($eventName) ? $eventName : array(
            'TYPE_ID' => $eventName,
        );

        $by = 'id';
        $order = 'asc';

        $dbres = \CEventMessage::GetList($by, $order, $filter);
        return $this->fetchAll($dbres);
    }

    /**
     * @param $eventName
     * @param $fields array(), key LID = language id
     * @return bool|int
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function addEventTypeIfNotExists($eventName, $fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('LID'));

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $item = \CEventType::GetList(array(
            'TYPE_ID' => $eventName,
            'LID' => $fields['LID']
        ))->Fetch();

        if ($item) {
            return $item['ID'];
        }

        $default = array(
            "LID" => $fields['LID'],
            "EVENT_NAME" => 'event_name',
            "NAME" => 'NAME',
            "DESCRIPTION" => 'description',
        );

        $fields = array_replace_recursive($default, $fields);
        $fields['EVENT_NAME'] = $eventName;

        $event = new \CEventType;
        $id = $event->Add($fields);

        if ($id) {
            return $id;
        }

        $this->throwException(__METHOD__, 'Event type %s not added', $eventName);
    }


    /**
     * @param $eventName
     * @param $fields array(), key LID = site id
     * @return int
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function addEventMessageIfNotExists($eventName, $fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('SUBJECT', 'LID'));

        $item = $this->getEventMessage(array(
            'TYPE_ID' => $eventName,
            'SUBJECT' => $fields['SUBJECT'],
        ));

        if ($item) {
            return $item['ID'];
        }

        return $this->addEventMessage($eventName, $fields);
    }


    public function updateEventMessage($eventName, $fields) {
        $items = $this->getEventMessages($eventName);

        foreach ($items as $item) {
            $this->updateEventMessageById($item["ID"], $fields);
        }

        return true;
    }

    public function updateEventMessageById($id, $fields) {
        $event = new \CEventMessage;
        if ($event->Update($id, $fields)) {
            return $id;
        }

        $this->throwException(__METHOD__, $event->LAST_ERROR);
    }

    //version 2

    public function saveEventMessage($eventName, $fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('SUBJECT', 'LID'));

        $item = $this->getEventMessage(array(
            'TYPE_ID' => $eventName,
            'SUBJECT' => $fields['SUBJECT'],
        ));

        if ($item) {
            return $this->updateEventMessageById($item['ID'], $fields);
        } else {
            return $this->addEventMessage($eventName, $fields);
        }
    }


    /** @deprecated */
    public function updateEventMessageByFilter($filter, $fields) {
        return $this->updateEventMessage($filter, $fields);
    }

    /** @deprecated */
    public function addEventType($eventName, $fields) {
        return $this->addEventTypeIfNotExists($eventName, $fields);
    }


    public function addEventMessage($eventName, $fields) {
        $default = array(
            'ACTIVE' => 'Y',
            'LID' => 's1',
            'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
            'EMAIL_TO' => '#EMAIL_TO#',
            'BCC' => '',
            'SUBJECT' => 'subject',
            'BODY_TYPE' => 'text',
            'MESSAGE' => 'message',
        );

        $fields = array_replace_recursive($default, $fields);
        $fields['EVENT_NAME'] = $eventName;

        $event = new \CEventMessage;
        $id = $event->Add($fields);

        if ($id) {
            return $id;
        }

        $this->throwException(__METHOD__, 'Event message %s not added, error: %s', $eventName, $event->LAST_ERROR);
    }
}