<?php
namespace Flarum\Support\Json;

/**
 * DTO to manage JSON error response handling.
 */
class ResponseBag
{
    private $status;
    private $errors;

    /**
     * @param integer $status
     * @param array $errors
     */
    public function __construct($status, array $errors)
    {
        $this->status = $status;
        $this->errors = $errors;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }
}
