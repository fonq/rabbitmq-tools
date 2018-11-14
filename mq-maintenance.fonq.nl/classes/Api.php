<?php

namespace Classes;

use Classes\Exception\HttpException;
use LogicException;
use Model\BaseModel;

class Api
{
    const HTTP_SUCCESS = 200;
    const HTTP_CREATED = 201;
    const HTTP_DELETED = 204;
    const HTTP_ACCESS_REFUSED = 401;
    const HTTP_SUCCESS_NO_OUTPUT = 204;

    private $api_host;
    private $api_port;

    function __construct($api_host, $api_port)
    {
        $this->api_host = $api_host;
        $this->api_port = $api_port;
    }

    private function validateEndpoint($endpoint)
    {
        if (strpos($endpoint, '/api/') === 0) {
            throw new LogicException("Please remove the /api part from your endpoint, it is automatically added.");
        }
        return $endpoint;
    }

    private function getUrl($endpoint)
    {
        return 'http://' . $this->api_host . ':' . $this->api_port . '/api' . $endpoint;
    }

    function isLoggedIn(): bool
    {
        $ch = $this->getCurl('/overview', 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($status_code == 401) {
            return false;
        }
        return true;
    }

    /**
     * @param string $endpoint part of the url that is unique for this request
     * @param string $requestType ['GET', 'PUT', 'POST', 'DELETE']
     * @return resource a curl resource
     */
    private function getCurl($endpoint, $requestType = 'GET')
    {
        if (!in_array($requestType, $allowedOptions = ['GET', 'PUT', 'POST', 'DELETE'])) {
            throw new LogicException("Expected " . join(", ", $allowedOptions) . ' as requestType');
        }

        $url = $this->getUrl($endpoint);
        Logger::log(User::getApiUser() . ' calls ' . $url, Logger::VERBOSE);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestType);

        $aHeaders = [];
        $aHeaders[] = 'Authorization: Basic ' . base64_encode(User::getApiUser() . ':' . User::getApiPass());

        if ($requestType != 'GET') {
            $aHeaders[] = 'content-type:application/json';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeaders);
        return $ch;
    }

    /**
     * @param $endpoint
     * @param BaseModel $model
     * @return mixed
     * @throws HttpException
     */
    function post($endpoint, BaseModel $model)
    {
        $endpoint = $this->validateEndpoint($endpoint);

        $ch = curl_init();

        $post_data = $model->toApi();
        $post_data_json = json_encode($post_data);
        $post_data_json = str_replace('\/', '/', $post_data_json);

        Logger::log('Payload POST: ' . $post_data_json, Logger::VERBOSE);

        $url = $this->getUrl($endpoint);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $aHeaders = [
            'Authorization: Basic ' . base64_encode(User::getApiUser() . ':' . User::getApiPass()),
            'content-type: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeaders);

        $output = curl_exec($ch);

        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        Logger::log('Statuscode POST: ' . $status_code, Logger::VERBOSE);
        Logger::log('Output POST: ' . $output, Logger::VERBOSE);

        if ($status_code != self::HTTP_SUCCESS && $status_code != self::HTTP_CREATED) {
            Logger::log($url, Logger::WARNING);
            Logger::log($output, Logger::WARNING);
            throw new HttpException("Something went wrong when posting data to $endpoint, got statuscode $status_code.",
                $status_code);
        }
        if (empty($output)) {
            return null;
        }
        return json_decode($output, true);
    }

    /**
     * @param $endpoint
     * @param BaseModel $model
     * @throws HttpException
     */
    function put($endpoint, BaseModel $model): void
    {
        $endpoint = $this->validateEndpoint($endpoint);

        $put_data = $model->toApi();
        $put_data_json = json_encode($put_data);

        Logger::log('Payload PUT: ' . $put_data_json, Logger::VERBOSE);

        $ch = $this->getCurl($endpoint, 'PUT');

        curl_setopt($ch, CURLOPT_POSTFIELDS, $put_data_json);
        $output = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        Logger::log('Statuscode PUT: ' . $status_code, Logger::VERBOSE);
        Logger::log('Output PUT: ' . $output, Logger::VERBOSE);

        if ($status_code !== self::HTTP_CREATED && $status_code != self::HTTP_SUCCESS_NO_OUTPUT) {
            echo $put_data_json . "<br><br>";
            echo "-------- <br><br>";
            echo $output;
            Logger::log($endpoint, Logger::WARNING);
            Logger::log($output, Logger::WARNING);
            throw new HttpException("API returned a $status_code.", $status_code);
        }
    }

    /**
     * @param $endpoint
     * @throws HttpException
     */
    function delete($endpoint): void
    {
        $endpoint = $this->validateEndpoint($endpoint);

        $ch = $this->getCurl($endpoint, 'DELETE');
        curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        Logger::log('Statuscode DELETE: ' . $status_code, Logger::VERBOSE);

        if ($status_code !== self::HTTP_DELETED) {
            Logger::log($endpoint, Logger::WARNING);
            throw new HttpException("API unexpected status code $status_code for DELETE to $endpoint.",
                $status_code);
        }
    }

    /**
     * @param $endpoint
     * @return mixed
     * @throws HttpException
     */
    function get($endpoint)
    {
        $endpoint = $this->validateEndpoint($endpoint);

        $ch = $this->getCurl($endpoint, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        Logger::log('Statuscode GET: ' . $status_code, Logger::VERBOSE);
        Logger::log('Output GET: ' . $output, Logger::VERBOSE);

        if ($status_code !== self::HTTP_SUCCESS) {
            Logger::log($endpoint, Logger::WARNING);
            Logger::log($output, Logger::WARNING);
            throw new HttpException("API unexpected status code $status_code for GET to $endpoint.", $status_code);
        }
        return json_decode($output, true);
    }
}
