<?php
include('lib/httpful.phar');

class Doppler_Service
{

  private $config;

  private $resources;

  private $httpClient;

  function __construct($config)
  {

    $this->config = [
      "credentials" =>[
        "api_key"     => $config[ 'api_key' ] ,
        "user_account"   => $config[ 'user_account' ]
      ]
    ];

    $this->baseUrl = 'https://restapi.fromdoppler.com/accounts/'. $config[ 'user_account' ] . '/';

    $this->resources = [
      'lists'   => new Doppler_Service_Lists_Resource(
        $this,
        array(
          'methods' => array(
            'get' => array(
              'route'        => 'lists/:listId',
              'httpMethod'  => 'get',
              'parameters'  => array(
                'listId' => array(
                  'on_query_string' => true,
                )
              )
            ),
            'list' => array(
              'route'       => 'lists',
              'httpMethod'  => 'get',
              'parameters'  => array(
                'per_page' => 100
              )
            )
          )
        )
      ),
      'fields'  => new Doppler_Service_Fields(
        $this,
        array(
          'methods' => array(
            'list' => array(
              'route'       => 'fields',
              'httpMethod'  => 'get',
              'parameters'  => null
            )
          )
        )
      ),
      'subscribers'  => new Doppler_Service_Subscribers(
        $this,
        array(
          'methods' => array(
            'post' => array(
              'route'       => 'lists/:listId/subscribers',
              'httpMethod'  => 'post',
              'parameters'  => array(
                'listId' => array(
                  'on_query_string' => false,
                )
              )
            )
          )
        )
      )
    ];

  }


  function setCredentials($credentials) {
    array_merge($credentials, $this->config['credentials'] );
  }

  function getConnectionStatus() {
    return $this->status;
  }

  private function checkStatus() {

    $url = sprintf('https://restapi.fromdoppler.com');

    $headers=array(
            "HTTP/1.1",
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: token ". $this->config["credentials"]["api_key"]
             );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_POST,0);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $response= curl_exec($curl);

    curl_close($curl);
    $request=json_decode($response,false, 512, JSON_BIGINT_AS_STRING );
    $aux = array();
    if( $request->status ){
      $aux[ "status" ] = $request->status;
      $aux[ "detail" ] = $request->title;
    }else{
      $aux[ "status" ] = 200;
      $href = $request->_links[1]->href;

      $account = explode( '/', $href )[2];

      $this->config["credentials"]["user_account"] = $account;
    }

    return $aux;
  }

  function call( $method, $args=null, $body=null ) {
    $url = $this->baseUrl;
    $url .= $method[ 'route' ];
    $query = "";
    if( $args && count($args)>0 ){
      $resourceArg = $method[ 'parameters' ];
      foreach ($args as $name => $val) {
        $parameter = $resourceArg[ $name ];
        if( $parameter && $parameter[ 'on_query_string' ] ){
          $query .= $arg . "=" . $val . "&";
        }else{
          $url = str_replace(":".$name, $val, $url);
        }
      }
      if(isset($resourceArg["per_page"])){
        $url.="?per_page=".$resourceArg["per_page"];
      }
    }

    $headers=array(
            "Accept" => "application/json",
            "Content-Type" => "application/json",
            "Authorization" => "token ". $this->config["credentials"]["api_key"]
             );
    $response = "";

    switch($method['httpMethod']){
      case 'get':
        $response = \Httpful\Request::get($url)
          ->addHeaders( $headers )
          ->send();
        break;
      case 'post':

        $response = \Httpful\Request::post($url)
          ->body( json_encode($body) )
          ->addHeaders( $headers )
          ->send();
        break;

    }
    return json_decode($response);

  }

  function getResource( $resourceName ) {
    return $this->resources[ $resourceName ];
  }
 }

  /**
   * These classes represent the different resources of the API.
   */
  class Doppler_Service_Lists_Resource {

    private $service;

    private $client;

    private $methods;

    function __construct( $service, $args )
    {
      $this->service = $service;
      $this->methods = isset($args['methods']) ? $args['methods'] : null;
    }

    public function getList( $listId ){
      $method = $methods['get'];
      return $this->service->call($method, array("listId" => $listId) );
    }

    public function getAllLists(){
      $method = $this->methods['list'];
      return $this->service->call($method, array("listId" => $listId) );
    }

  }

  class Doppler_Service_Fields {

    private $service;

    private $client;

    private $methods;

    function __construct( $service, $args )
    {
      $this->service = $service;
      $this->methods = isset($args['methods']) ? $args['methods'] : null;
    }

    public function getAllFields(){
      $method = $this->methods['list'];
      return $this->service->call($method, array("listId" => $listId) );
    }

  }

  class Doppler_Service_Subscribers {

    private $service;

    private $client;

    private $methods;

    function __construct( $service, $args )
    {
      $this->service = $service;
      $this->methods = isset($args['methods']) ? $args['methods'] : null;
    }

    public function addSubscriber( $listId, $subscriber ){
      $method = $this->methods['post'];
      return $this->service->call( $method, array( 'listId' => $listId ),  $subscriber );
    }

  }


 ?>
