<?php

namespace EDI\Generator;

/**
 * Class Base
 *
 * @package EDI\Generator
 */
class Base
{

    /** @var array */
    protected $messageContent = [];

    /** @var array */
    protected $composed;

    /** @var string */
    protected $sender;

    /** @var string */
    protected $receiver;

    /** @var string */
//    protected $managingOrganisation = '89';

    /**
     * @param $keyName
     */
    public function addKeyToCompose($keyName)
    {
        $this->composeKeys[] = $keyName;
    }

    /**
     * compose message by keys givven in an ordered array
     *
     * @param array $keys
     *
     * @throws EdifactException
     * @return array
     */
    public function composeByKeys($keys = null)
    {
        if ($keys === null) {
            $keys = $this->composeKeys;
        }

        foreach ($keys as $key) {
            if (property_exists($this, $key)) {
                if ($this->{$key} !== null) {
                    $value = $this->{$key};
                    if ($value) {
                        /**
                         * Flaky and hacky but might work for IMD values?
                         */
                        if (count($value) === 1) {
                            foreach ($value as $subValue) {
                                $this->messageContent[] = $subValue;
                            }
                        } else {
                            $this->messageContent[] = $value;
                        }

                    } else {
                        throw new EdifactException("key " . $key . " returns no array structure");
                    }
                }
            } else {
                throw new EdifactException(
                    'key: ' . $key . ' not found for composeByKeys in ' . get_class($this) . '->' .
                    debug_backtrace()[1]['function']
                );
            }
        }

        return $this->messageContent;
    }

    /**
     * @return array
     */
    public function getComposed()
    {
        return $this->composed;
    }

    /**
     * @return string
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param string $sender
     *
     * @return $this
     */
    public function setSender($sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * @return string
     */
    public function getReceiver()
    {
        return $this->receiver;
    }

    /**
     * @param string $receiver
     *
     * @return $this
     */
    public function setReceiver($receiver)
    {
        $this->receiver = $receiver;

        return $this;
    }


    /**
     * @param string, $functionCode
     * @param $identifier
     *
     * @return array|bool
     */
    protected function addRFFSegment($functionCode, $identifier)
    {
        if (empty($identifier)) {
            return false;
        }

        return [
            'RFF',
            [
                $functionCode,
                self::maxChars($identifier, 35),
            ],
        ];
    }

    /**
     * @param     $dateString
     * @param     $type
     * @param int $formatQualifier
     *
     * @throws EdifactException
     * @return array
     * @see http://www.unece.org/trade/untdid/d96a/trsd/trsddtm.htm
     */
    protected function addDTMSegment($dateString, $type, $formatQualifier = EdifactDate::DATE)
    {
        $data = [];
        $data[] = (string) $type;
        if (! empty($dateString)) {
            $data[] = EdifactDate::get($dateString, $formatQualifier);
            $data[] = (string) $formatQualifier;
        }


        $res = ['DTM', $data];

        return $res;
    }

    /**
     * @param $documentNumber
     * @param $type
     *
     * @return array
     */
    public static function addBGMSegment($documentNumber, $type)
    {
        return [
            'BGM',
            [
                $type,
                '',
                '89',
            ],
            $documentNumber,
        ];
    }

    /**
     * Crop String to max char length
     *
     * @param string $string
     * @param int $length
     *
     * @return string
     */
    protected static function maxChars($string, $length = 35)
    {
        if (empty($string)) {
            return '';
        }

        return mb_substr($string, 0, $length);
    }

    /**
     *
     * @param      $value
     * @param      $array
     * @param null $errorMessage
     *
     * @throws EdifactException
     */
    protected function isAllowed($value, $array, $errorMessage = null)
    {
        if ($errorMessage === null) {
            $errorMessage = 'value: ' . $value . ' is not in allowed values: ' .
                ' [' . implode(', ', $array) . '] in ' . get_class($this) . '->' .
                debug_backtrace()[1]['function'];
        }
        if (! in_array($value, $array, true)) {
            throw new EdifactException($errorMessage);
        }
    }


    /**
     * @param $qualifier
     * @param $value
     *
     * @return array
     */
    public static function addMOASegment($qualifier, $value)
    {
        return [
            'MOA',
            [
                '',
                (string) $qualifier,
                EdiFactNumber::convert($value),
            ],
        ];
    }
}
