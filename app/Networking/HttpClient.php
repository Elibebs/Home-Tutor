<?php 

namespace App\Networking;

use \GuzzleHttp\Client;

class HttpClient
{
	protected $client;
	protected $method;
	protected $data;
	protected $response;

	public function __construct($method=null,$data=null)
	{
		$this->method = $method;
		$this->data = $data;

		$this->client = new Client(
			[
				'base_uri' => env("API_BASE_ENDPOINT",""),
     			'headers'  => ['content-type' => 'application/json', 'Accept' => 'application/json']
			]
		);
	}

	public function setMethod($method)
	{
		$this->method = $method;
	}

	public function getMethod()
	{
		return $this->method;
	}

	public function setUrl($url)
	{
		$this->url = $url;
	}

	public function getUrl()
	{
		return $this->url;
	}

	public function setData($data)
	{
		$this->data = $data;
	}

	public function getData()
	{
		return $this->data;
	}


	public function makeRequest($method=null,$url=null,$data=null)
	{
		$this->response = $this->client->request(
			$method ? $method : $this->method,
			$url ? $url : $this->url,
			$data ? ['json' => $data] : $this->data	? ['json' => $this->data] : []
		);
	}

	public function getStatusCode()
	{
		return $response->getStatusCode();
	}

	public function getResponseContent()
	{
		return json_decode($this->response->getBody()->getContents());
	}	

	public function getBody()
	{
		return $this->response->getBody();
	}

	public function getHeaders()
	{
		return $this->response->getHeaders();
	}
}