<?php

/*
 */

/**
 * Description of Json
 *
 * @author topman
 */
class Json {

    protected $current;

    /**
     * Class constructor
     * @param string|array|object $stringOrArrayOrObject Value to act upon
     */
    public function __construct($stringOrArrayOrObject = array()) {
        $this->current = $stringOrArrayOrObject;
    }
    
    public function addData($data, $key = null) {
        $this->current[$key] = $data;
        return $this;
    }

    /**
     * Returns the JSON representation of the given value
     * @see json_encode()
     * @param int $options [optional] <p>
     * Bitmask consisting of <b>JSON_HEX_QUOT</b>,
     * <b>JSON_HEX_TAG</b>,
     * <b>JSON_HEX_AMP</b>,
     * <b>JSON_HEX_APOS</b>,
     * <b>JSON_NUMERIC_CHECK</b>,
     * <b>JSON_PRETTY_PRINT</b>,
     * <b>JSON_UNESCAPED_SLASHES</b>,
     * <b>JSON_FORCE_OBJECT</b>,
     * <b>JSON_UNESCAPED_UNICODE</b>.
     * </p>
     * @param int $depth [optional] <p>
     * Set the maximum depth. Must be greater than zero.
     * </p>
     * @return string a JSON encoded string on success or <b>FALSE</b> on failure.
     */
    public function encode($options = 0) {
        return json_encode($this->current, $options);
    }

    /**
     * Decodes the given JSON string
     *
     * @see json_encode()
     * @param bool $assoc [optional] <p>
     * When <b>TRUE</b>, returned objects will be converted into
     * associative arrays.
     * </p>
     * @param int $depth [optional] <p>
     * User specified recursion depth.
     * </p>
     * @param int $options [optional] <p>
     * Bitmask of JSON decode options. Currently only
     * <b>JSON_BIGINT_AS_STRING</b>
     * is supported (default is to cast large integers as floats)
     * </p>
     * @return mixed the value encoded in <i>json</i> in appropriate
     * PHP type. Values true, false and
     * null (case-insensitive) are returned as <b>TRUE</b>, <b>FALSE</b>
     * and <b>NULL</b> respectively. <b>NULL</b> is returned if the
     * <i>json</i> cannot be decoded or if the encoded
     * data is deeper than the recursion limit.
     */
    public function decode($assoc = false, $depth = 512, $options = 0) {
        return json_decode($this->current, $assoc, $depth, $options);
    }

    /**
     * Sends the json to the screen as an object
     * @param boolean $terminate Indicates whether to exit all scripts after sending
     * to screen or allow scripts execution to continue
     */
    public function toScreen($terminate = false) {
        header('Content-Type: application/json');
        echo $this->encode();
        if ($terminate)
            exit;
    }

}
